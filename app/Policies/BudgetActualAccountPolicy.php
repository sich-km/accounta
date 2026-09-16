<?php

namespace App\Policies;

use App\Models\BudgetActualAccount;
use App\Models\User;

class BudgetActualAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BudgetActualAccount $budgetActualAccount): bool
    {
        return $user->organization_id === $budgetActualAccount->organization_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canManageMasters();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BudgetActualAccount $budgetActualAccount): bool
    {
        return $user->canManageMasters()
            && $user->organization_id === $budgetActualAccount->organization_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BudgetActualAccount $budgetActualAccount): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BudgetActualAccount $budgetActualAccount): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BudgetActualAccount $budgetActualAccount): bool
    {
        return false;
    }
}
