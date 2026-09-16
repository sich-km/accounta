<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonthlyAmountRequest;
use App\Http\Requests\UpdateMonthlyAmountRequest;
use App\Models\Department;
use App\Models\ManagementAccount;
use App\Models\MonthlyAmount;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyAmountController extends Controller
{
    /** @var list<int> */
    private const array PER_PAGE_OPTIONS = [50, 100, 150, 200];

    public function index(Request $request): View
    {
        $organizationId = $request->user()->organization_id;
        $availableYears = MonthlyAmount::query()
            ->forOrganization($organizationId)
            ->select('period')
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period')
            ->map(fn (string $period): int => CarbonImmutable::parse($period)->year)
            ->unique()
            ->values()
            ->all();
        $departments = Department::query()
            ->forOrganization($organizationId)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        $managementAccounts = ManagementAccount::query()
            ->forOrganization($organizationId)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $selectedType = $this->selectedType($request->query('type'));
        $selectedYear = $this->selectedYear($request->query('year'), $availableYears);
        $selectedMonth = $this->selectedMonth($request->query('month'), $selectedYear);
        $selectedDepartmentId = $this->selectedMasterId(
            $request->query('department_id'),
            $departments->pluck('id')->all(),
        );
        $selectedManagementAccountId = $this->selectedMasterId(
            $request->query('management_account_id'),
            $managementAccounts->pluck('id')->all(),
        );
        $selectedPerPage = $this->selectedPerPage($request->query('per_page'));

        $amounts = MonthlyAmount::query()
            ->forOrganization($organizationId)
            ->when($selectedType !== null, fn ($query) => $query->where('type', $selectedType))
            ->when($selectedDepartmentId !== null, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->when($selectedManagementAccountId !== null, fn ($query) => $query->where('management_account_id', $selectedManagementAccountId))
            ->when($selectedYear !== null, function ($query) use ($selectedMonth, $selectedYear): void {
                $periodStart = CarbonImmutable::create($selectedYear, $selectedMonth ?? 1, 1);
                $periodEnd = $selectedMonth === null
                    ? $periodStart->endOfYear()
                    : $periodStart->endOfMonth();

                $query->whereBetween('period', [$periodStart->toDateString(), $periodEnd->toDateString()]);
            })
            ->with(['department', 'managementAccount'])
            ->orderByDesc('period')
            ->orderByDesc('id')
            ->paginate($selectedPerPage)
            ->withQueryString();

        return view('amounts.index', [
            'amounts' => $amounts,
            'amountTypes' => MonthlyAmount::TYPES,
            'availableYears' => $availableYears,
            'departments' => $departments,
            'managementAccounts' => $managementAccounts,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'selectedType' => $selectedType,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedManagementAccountId' => $selectedManagementAccountId,
            'selectedPerPage' => $selectedPerPage,
        ]);
    }

    public function create(Request $request): View
    {
        return view('amounts.create', [
            ...$this->masterData($request),
            'amountTypes' => MonthlyAmount::TYPES,
        ]);
    }

    public function store(StoreMonthlyAmountRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['period'] = CarbonImmutable::createFromFormat('!Y-m-d', $validated['period'].'-01');

        $request->user()
            ->organization
            ->monthlyAmounts()
            ->create($validated);

        return redirect()
            ->route('amounts.index')
            ->with('status', '予算・実績明細を登録しました。');
    }

    public function edit(Request $request, int $amount): View
    {
        $amount = $this->ownedAmount($request, $amount);

        return view('amounts.edit', [
            'amount' => $amount,
            ...$this->masterData($request, $amount),
            'amountTypes' => MonthlyAmount::TYPES,
        ]);
    }

    public function update(UpdateMonthlyAmountRequest $request, int $amount): RedirectResponse
    {
        $amount = $this->ownedAmount($request, $amount);
        $validated = $request->validated();
        $validated['period'] = CarbonImmutable::createFromFormat('!Y-m-d', $validated['period'].'-01');
        $amount->update($validated);

        return redirect()
            ->route('amounts.index')
            ->with('status', '予算・実績明細を更新しました。');
    }

    public function destroy(Request $request, int $amount): RedirectResponse
    {
        $this->ownedAmount($request, $amount)->delete();

        return redirect()
            ->route('amounts.index')
            ->with('status', '予算・実績明細を削除しました。');
    }

    /**
     * @return array{departments: Collection<int, Department>, managementAccounts: Collection<int, ManagementAccount>}
     */
    private function masterData(Request $request, ?MonthlyAmount $amount = null): array
    {
        $organizationId = $request->user()->organization_id;

        $departments = Department::query()
            ->forOrganization($organizationId)
            ->where(function ($query) use ($amount): void {
                $query->where('is_active', true)
                    ->when($amount, fn ($activeQuery) => $activeQuery->orWhere('id', $amount->department_id));
            })
            ->orderBy('code')
            ->get();

        $managementAccounts = ManagementAccount::query()
            ->forOrganization($organizationId)
            ->where(function ($query) use ($amount): void {
                $query->where('is_active', true)
                    ->when($amount, fn ($activeQuery) => $activeQuery->orWhere('id', $amount->management_account_id));
            })
            ->orderBy('code')
            ->get();

        return compact('departments', 'managementAccounts');
    }

    private function ownedAmount(Request $request, int $amountId): MonthlyAmount
    {
        return MonthlyAmount::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($amountId);
    }

    private function selectedType(mixed $type): ?string
    {
        return is_string($type) && array_key_exists($type, MonthlyAmount::TYPES)
            ? $type
            : null;
    }

    /**
     * @param  list<int>  $availableYears
     */
    private function selectedYear(mixed $year, array $availableYears): ?int
    {
        $selectedYear = filter_var($year, FILTER_VALIDATE_INT);

        return $selectedYear !== false && in_array($selectedYear, $availableYears, true)
            ? $selectedYear
            : null;
    }

    private function selectedMonth(mixed $month, ?int $selectedYear): ?int
    {
        if ($selectedYear === null) {
            return null;
        }

        $selectedMonth = filter_var($month, FILTER_VALIDATE_INT);

        return $selectedMonth !== false && $selectedMonth >= 1 && $selectedMonth <= 12
            ? $selectedMonth
            : null;
    }

    /**
     * @param  list<int>  $availableIds
     */
    private function selectedMasterId(mixed $id, array $availableIds): ?int
    {
        $selectedId = filter_var($id, FILTER_VALIDATE_INT);

        return $selectedId !== false && in_array($selectedId, $availableIds, true)
            ? $selectedId
            : null;
    }

    private function selectedPerPage(mixed $perPage): int
    {
        $selectedPerPage = filter_var($perPage, FILTER_VALIDATE_INT);

        return $selectedPerPage !== false && in_array($selectedPerPage, self::PER_PAGE_OPTIONS, true)
            ? $selectedPerPage
            : self::PER_PAGE_OPTIONS[0];
    }
}
