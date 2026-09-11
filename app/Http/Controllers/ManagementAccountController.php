<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManagementAccountRequest;
use App\Http\Requests\UpdateManagementAccountRequest;
use App\Models\ManagementAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ManagementAccountController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ManagementAccount::class);

        $managementAccounts = ManagementAccount::query()
            ->forOrganization($request->user()->organization_id)
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(50);

        return view('management-accounts.index', compact('managementAccounts'));
    }

    public function create(): View
    {
        Gate::authorize('create', ManagementAccount::class);

        return view('management-accounts.create', [
            'accountTypes' => ManagementAccount::TYPES,
        ]);
    }

    public function store(StoreManagementAccountRequest $request): RedirectResponse
    {
        $request->user()
            ->organization
            ->managementAccounts()
            ->create($request->validated());

        return redirect()
            ->route('management-accounts.index')
            ->with('status', '予実管理科目を登録しました。');
    }

    public function edit(Request $request, int $managementAccount): View
    {
        $managementAccount = $this->ownedManagementAccount($request, $managementAccount);
        Gate::authorize('update', $managementAccount);

        return view('management-accounts.edit', [
            'managementAccount' => $managementAccount,
            'accountTypes' => ManagementAccount::TYPES,
        ]);
    }

    public function update(UpdateManagementAccountRequest $request, int $managementAccount): RedirectResponse
    {
        $managementAccount = $this->ownedManagementAccount($request, $managementAccount);
        Gate::authorize('update', $managementAccount);
        $managementAccount->update($request->validated());

        return redirect()
            ->route('management-accounts.index')
            ->with('status', '予実管理科目を更新しました。');
    }

    public function toggleStatus(Request $request, int $managementAccount): RedirectResponse
    {
        $managementAccount = $this->ownedManagementAccount($request, $managementAccount);
        Gate::authorize('update', $managementAccount);
        $managementAccount->update(['is_active' => ! $managementAccount->is_active]);

        return back()->with(
            'status',
            $managementAccount->is_active ? '予実管理科目を有効にしました。' : '予実管理科目を無効にしました。'
        );
    }

    private function ownedManagementAccount(Request $request, int $managementAccountId): ManagementAccount
    {
        return ManagementAccount::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($managementAccountId);
    }
}
