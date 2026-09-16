<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetActualAccountRequest;
use App\Http\Requests\UpdateBudgetActualAccountRequest;
use App\Models\BudgetActualAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BudgetActualAccountController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', BudgetActualAccount::class);

        $budgetActualAccounts = BudgetActualAccount::query()
            ->forOrganization($request->user()->organization_id)
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(50);

        return view('budget-actual-accounts.index', compact('budgetActualAccounts'));
    }

    public function create(): View
    {
        Gate::authorize('create', BudgetActualAccount::class);

        return view('budget-actual-accounts.create', [
            'accountTypes' => BudgetActualAccount::TYPES,
        ]);
    }

    public function store(StoreBudgetActualAccountRequest $request): RedirectResponse
    {
        $request->user()
            ->organization
            ->budgetActualAccounts()
            ->create($request->validated());

        return redirect()
            ->route('budget-actual-accounts.index')
            ->with('status', '予実管理科目を登録しました。');
    }

    public function edit(Request $request, int $budgetActualAccount): View
    {
        $budgetActualAccount = $this->ownedBudgetActualAccount($request, $budgetActualAccount);
        Gate::authorize('update', $budgetActualAccount);

        return view('budget-actual-accounts.edit', [
            'budgetActualAccount' => $budgetActualAccount,
            'accountTypes' => BudgetActualAccount::TYPES,
        ]);
    }

    public function update(UpdateBudgetActualAccountRequest $request, int $budgetActualAccount): RedirectResponse
    {
        $budgetActualAccount = $this->ownedBudgetActualAccount($request, $budgetActualAccount);
        Gate::authorize('update', $budgetActualAccount);
        $budgetActualAccount->update($request->validated());

        return redirect()
            ->route('budget-actual-accounts.index')
            ->with('status', '予実管理科目を更新しました。');
    }

    public function toggleStatus(Request $request, int $budgetActualAccount): RedirectResponse
    {
        $budgetActualAccount = $this->ownedBudgetActualAccount($request, $budgetActualAccount);
        Gate::authorize('update', $budgetActualAccount);
        $budgetActualAccount->update(['is_active' => ! $budgetActualAccount->is_active]);

        return back()->with(
            'status',
            $budgetActualAccount->is_active ? '予実管理科目を有効にしました。' : '予実管理科目を無効にしました。'
        );
    }

    private function ownedBudgetActualAccount(Request $request, int $budgetActualAccountId): BudgetActualAccount
    {
        return BudgetActualAccount::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($budgetActualAccountId);
    }
}
