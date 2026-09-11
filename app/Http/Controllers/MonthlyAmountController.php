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
    public function index(Request $request): View
    {
        $amounts = MonthlyAmount::query()
            ->forOrganization($request->user()->organization_id)
            ->with(['department', 'managementAccount'])
            ->orderByDesc('period')
            ->orderByDesc('id')
            ->paginate(20);

        return view('amounts.index', compact('amounts'));
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
}
