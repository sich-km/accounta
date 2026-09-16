<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetActualEntryRequest;
use App\Http\Requests\UpdateBudgetActualEntryRequest;
use App\Models\BudgetActualAccount;
use App\Models\BudgetActualEntry;
use App\Models\Department;
use App\Services\FiscalYearService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BudgetActualEntryController extends Controller
{
    /** @var list<int> */
    private const array PER_PAGE_OPTIONS = [50, 100, 150, 200];

    private const string PREFERENCES_CACHE_PREFIX = 'budget-actual-entries:index-preferences:user:';

    public function index(Request $request, FiscalYearService $fiscalYearService): View
    {
        $organizationId = $request->user()->organization_id;
        $fiscalYearStartMonth = (int) $request->user()->company()->value('fiscal_year_start_month');
        $currentFiscalYear = $fiscalYearService->current($fiscalYearStartMonth);
        $availablePeriods = BudgetActualEntry::query()
            ->forOrganization($organizationId)
            ->select('period')
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period');
        $availableYears = $fiscalYearService->availableYears(
            $availablePeriods,
            $fiscalYearStartMonth,
        );
        $departments = Department::query()
            ->forOrganization($organizationId)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        $budgetActualAccounts = BudgetActualAccount::query()
            ->forOrganization($organizationId)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $preferencesCacheKey = self::PREFERENCES_CACHE_PREFIX.$request->user()->getAuthIdentifier();
        $preferenceKeys = ['type', 'year', 'month', 'department_id', 'budget_actual_account_id', 'per_page'];
        $defaultPreferences = [
            'type' => null,
            'year' => $currentFiscalYear,
            'month' => null,
            'department_id' => null,
            'budget_actual_account_id' => null,
            'per_page' => self::PER_PAGE_OPTIONS[0],
        ];

        if ($request->boolean('reset_filters')) {
            Cache::forget($preferencesCacheKey);
        }

        $storedPreferences = Cache::get($preferencesCacheKey, []);
        $requestedPreferences = match (true) {
            $request->boolean('reset_filters') => $defaultPreferences,
            $request->hasAny($preferenceKeys) => array_replace(
                $defaultPreferences,
                $request->only($preferenceKeys),
            ),
            is_array($storedPreferences) && $storedPreferences !== [] => array_replace(
                $defaultPreferences,
                $storedPreferences,
            ),
            default => $defaultPreferences,
        };

        $selectedType = $this->selectedType($requestedPreferences['type']);
        $selectedYear = $this->selectedYear(
            $requestedPreferences['year'],
            $availableYears,
            $currentFiscalYear,
        );
        $selectedMonth = $this->selectedMonth($requestedPreferences['month'], $selectedYear);
        $selectedDepartmentId = $this->selectedMasterId(
            $requestedPreferences['department_id'],
            $departments->pluck('id')->all(),
        );
        $selectedBudgetActualAccountId = $this->selectedMasterId(
            $requestedPreferences['budget_actual_account_id'],
            $budgetActualAccounts->pluck('id')->all(),
        );
        $selectedPerPage = $this->selectedPerPage($requestedPreferences['per_page']);

        $normalizedPreferences = [
            'type' => $selectedType,
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'department_id' => $selectedDepartmentId,
            'budget_actual_account_id' => $selectedBudgetActualAccountId,
            'per_page' => $selectedPerPage,
        ];

        if ($storedPreferences !== $normalizedPreferences) {
            Cache::forever($preferencesCacheKey, $normalizedPreferences);
        }

        $dateRange = $selectedYear === null
            ? null
            : $this->fiscalYearDateRange(
                $selectedYear,
                $fiscalYearStartMonth,
                $selectedMonth,
            );

        $budgetActualEntries = BudgetActualEntry::query()
            ->forOrganization($organizationId)
            ->when($selectedType !== null, fn ($query) => $query->where('type', $selectedType))
            ->when($selectedDepartmentId !== null, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->when($selectedBudgetActualAccountId !== null, fn ($query) => $query->where('budget_actual_account_id', $selectedBudgetActualAccountId))
            ->when($dateRange !== null, fn ($query) => $query->whereBetween('period', $dateRange))
            ->with(['department', 'budgetActualAccount'])
            ->orderByDesc('period')
            ->orderByDesc('id')
            ->paginate($selectedPerPage)
            ->withQueryString();

        return view('budget-actual-entries.index', [
            'budgetActualEntries' => $budgetActualEntries,
            'entryTypes' => BudgetActualEntry::TYPES,
            'availableYears' => $availableYears,
            'currentFiscalYear' => $currentFiscalYear,
            'fiscalYearMonths' => array_merge(
                range($fiscalYearStartMonth, 12),
                $fiscalYearStartMonth === 1 ? [] : range(1, $fiscalYearStartMonth - 1),
            ),
            'departments' => $departments,
            'budgetActualAccounts' => $budgetActualAccounts,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'selectedType' => $selectedType,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedBudgetActualAccountId' => $selectedBudgetActualAccountId,
            'selectedPerPage' => $selectedPerPage,
        ]);
    }

    public function create(Request $request): View
    {
        return view('budget-actual-entries.create', [
            ...$this->masterData($request),
            'entryTypes' => BudgetActualEntry::TYPES,
        ]);
    }

    public function store(StoreBudgetActualEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['period'] = CarbonImmutable::createFromFormat('!Y-m-d', $validated['period'].'-01');

        $request->user()
            ->organization
            ->budgetActualEntries()
            ->create($validated);

        return redirect()
            ->route('budget-actual-entries.index')
            ->with('status', '予算・実績明細を登録しました。');
    }

    public function edit(Request $request, int $budgetActualEntry): View
    {
        $budgetActualEntry = $this->ownedBudgetActualEntry($request, $budgetActualEntry);

        return view('budget-actual-entries.edit', [
            'budgetActualEntry' => $budgetActualEntry,
            ...$this->masterData($request, $budgetActualEntry),
            'entryTypes' => BudgetActualEntry::TYPES,
        ]);
    }

    public function update(UpdateBudgetActualEntryRequest $request, int $budgetActualEntry): RedirectResponse
    {
        $budgetActualEntry = $this->ownedBudgetActualEntry($request, $budgetActualEntry);
        $validated = $request->validated();
        $validated['period'] = CarbonImmutable::createFromFormat('!Y-m-d', $validated['period'].'-01');
        $budgetActualEntry->update($validated);

        return redirect()
            ->route('budget-actual-entries.index')
            ->with('status', '予算・実績明細を更新しました。');
    }

    public function destroy(Request $request, int $budgetActualEntry): RedirectResponse
    {
        $this->ownedBudgetActualEntry($request, $budgetActualEntry)->delete();

        return redirect()
            ->route('budget-actual-entries.index')
            ->with('status', '予算・実績明細を削除しました。');
    }

    /**
     * @return array{departments: Collection<int, Department>, budgetActualAccounts: Collection<int, BudgetActualAccount>}
     */
    private function masterData(Request $request, ?BudgetActualEntry $budgetActualEntry = null): array
    {
        $organizationId = $request->user()->organization_id;

        $departments = Department::query()
            ->forOrganization($organizationId)
            ->where(function ($query) use ($budgetActualEntry): void {
                $query->where('is_active', true)
                    ->when($budgetActualEntry, fn ($activeQuery) => $activeQuery->orWhere('id', $budgetActualEntry->department_id));
            })
            ->orderBy('code')
            ->get();

        $budgetActualAccounts = BudgetActualAccount::query()
            ->forOrganization($organizationId)
            ->where(function ($query) use ($budgetActualEntry): void {
                $query->where('is_active', true)
                    ->when($budgetActualEntry, fn ($activeQuery) => $activeQuery->orWhere('id', $budgetActualEntry->budget_actual_account_id));
            })
            ->orderBy('code')
            ->get();

        return compact('departments', 'budgetActualAccounts');
    }

    private function ownedBudgetActualEntry(Request $request, int $budgetActualEntryId): BudgetActualEntry
    {
        return BudgetActualEntry::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($budgetActualEntryId);
    }

    private function selectedType(mixed $type): ?string
    {
        return is_string($type) && array_key_exists($type, BudgetActualEntry::TYPES)
            ? $type
            : null;
    }

    /**
     * @param  list<int>  $availableYears
     */
    private function selectedYear(mixed $year, array $availableYears, int $currentFiscalYear): ?int
    {
        if ($year === null || $year === '') {
            return null;
        }

        $selectedYear = filter_var($year, FILTER_VALIDATE_INT);

        return $selectedYear !== false && in_array($selectedYear, $availableYears, true)
            ? $selectedYear
            : $currentFiscalYear;
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

    /**
     * @return array{0: string, 1: string}
     */
    private function fiscalYearDateRange(
        int $fiscalYear,
        int $fiscalYearStartMonth,
        ?int $selectedMonth,
    ): array {
        if ($selectedMonth === null) {
            $periodStart = CarbonImmutable::create($fiscalYear, $fiscalYearStartMonth, 1);

            return [
                $periodStart->toDateString(),
                $periodStart->addYear()->subDay()->toDateString(),
            ];
        }

        $calendarYear = $selectedMonth >= $fiscalYearStartMonth
            ? $fiscalYear
            : $fiscalYear + 1;
        $periodStart = CarbonImmutable::create($calendarYear, $selectedMonth, 1);

        return [
            $periodStart->toDateString(),
            $periodStart->endOfMonth()->toDateString(),
        ];
    }
}
