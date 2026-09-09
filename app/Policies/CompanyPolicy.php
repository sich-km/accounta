<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Company $company): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Company $company): bool
    {
        $hasOrganizations = $company->getAttribute('organizations_count') !== null
            ? (int) $company->getAttribute('organizations_count') > 0
            : $company->organizations()->exists();
        $hasUsers = $company->getAttribute('users_count') !== null
            ? (int) $company->getAttribute('users_count') > 0
            : $company->users()->exists();

        return $user->isAdmin()
            && $user->company_id !== $company->id
            && ! $hasOrganizations
            && ! $hasUsers;
    }

    public function restore(User $user, Company $company): bool
    {
        return false;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return false;
    }
}
