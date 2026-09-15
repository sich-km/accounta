<?php

namespace App\Policies;

use App\Models\LedgerAccount;
use App\Models\User;

class LedgerAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    public function view(User $user, LedgerAccount $ledgerAccount): bool
    {
        return $user->organization_id === $ledgerAccount->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->canManageMasters();
    }

    public function update(User $user, LedgerAccount $ledgerAccount): bool
    {
        return $user->canManageMasters()
            && $user->organization_id === $ledgerAccount->organization_id;
    }

    public function delete(User $user, LedgerAccount $ledgerAccount): bool
    {
        return false;
    }

    public function restore(User $user, LedgerAccount $ledgerAccount): bool
    {
        return false;
    }

    public function forceDelete(User $user, LedgerAccount $ledgerAccount): bool
    {
        return false;
    }
}
