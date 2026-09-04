<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = Account::query()
            ->forOrganization($request->user()->organization_id)
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(50);

        return view('accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('accounts.create', ['accountTypes' => Account::TYPES]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $request->user()
            ->organization
            ->accounts()
            ->create($request->validated());

        return redirect()
            ->route('accounts.index')
            ->with('status', '勘定科目を登録しました。');
    }

    public function edit(Request $request, int $account): View
    {
        $account = $this->ownedAccount($request, $account);

        return view('accounts.edit', [
            'account' => $account,
            'accountTypes' => Account::TYPES,
        ]);
    }

    public function update(UpdateAccountRequest $request, int $account): RedirectResponse
    {
        $account = $this->ownedAccount($request, $account);
        $account->update($request->validated());

        return redirect()
            ->route('accounts.index')
            ->with('status', '勘定科目を更新しました。');
    }

    public function toggleStatus(Request $request, int $account): RedirectResponse
    {
        $account = $this->ownedAccount($request, $account);
        $account->update(['is_active' => ! $account->is_active]);

        return back()->with(
            'status',
            $account->is_active ? '勘定科目を有効にしました。' : '勘定科目を無効にしました。'
        );
    }

    private function ownedAccount(Request $request, int $accountId): Account
    {
        return Account::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($accountId);
    }
}
