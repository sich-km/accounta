<?php

namespace App\Http\Controllers;

use App\Enums\JournalSide;
use App\Enums\LedgerAccountType;
use App\Http\Requests\StoreLedgerAccountRequest;
use App\Http\Requests\UpdateLedgerAccountRequest;
use App\Models\LedgerAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LedgerAccountController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LedgerAccount::class);

        $ledgerAccounts = LedgerAccount::query()
            ->forOrganization($request->user()->organization_id)
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(50);

        return view('ledger-accounts.index', compact('ledgerAccounts'));
    }

    public function create(): View
    {
        Gate::authorize('create', LedgerAccount::class);

        return view('ledger-accounts.create', $this->formOptions());
    }

    public function store(StoreLedgerAccountRequest $request): RedirectResponse
    {
        $request->user()->organization->ledgerAccounts()->create($request->validated());

        return redirect()->route('ledger-accounts.index')->with('status', '仕訳用勘定科目を登録しました。');
    }

    public function edit(Request $request, int $ledgerAccount): View
    {
        $ledgerAccount = $this->ownedLedgerAccount($request, $ledgerAccount);
        Gate::authorize('update', $ledgerAccount);

        return view('ledger-accounts.edit', [
            'ledgerAccount' => $ledgerAccount,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateLedgerAccountRequest $request, int $ledgerAccount): RedirectResponse
    {
        $ledgerAccount = $this->ownedLedgerAccount($request, $ledgerAccount);
        Gate::authorize('update', $ledgerAccount);
        $ledgerAccount->update($request->validated());

        return redirect()->route('ledger-accounts.index')->with('status', '仕訳用勘定科目を更新しました。');
    }

    public function updateStatus(Request $request, int $ledgerAccount): RedirectResponse
    {
        $ledgerAccount = $this->ownedLedgerAccount($request, $ledgerAccount);
        Gate::authorize('update', $ledgerAccount);
        $ledgerAccount->update(['is_active' => ! $ledgerAccount->is_active]);

        return back()->with('status', $ledgerAccount->is_active
            ? '仕訳用勘定科目を有効にしました。'
            : '仕訳用勘定科目を無効にしました。');
    }

    /** @return array{accountTypes: list<LedgerAccountType>, journalSides: list<JournalSide>} */
    private function formOptions(): array
    {
        return [
            'accountTypes' => LedgerAccountType::cases(),
            'journalSides' => JournalSide::cases(),
        ];
    }

    private function ownedLedgerAccount(Request $request, int $ledgerAccountId): LedgerAccount
    {
        return LedgerAccount::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($ledgerAccountId);
    }
}
