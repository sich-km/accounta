<?php

use App\Enums\UserType;
use App\Models\BudgetActualAccount;
use App\Models\Organization;
use App\Models\User;

test('all user types can view their organization budget actual accounts', function (UserType $userType) {
    $user = User::factory()->create(['user_type' => $userType]);
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();

    expect($user->can('viewAny', BudgetActualAccount::class))->toBeTrue()
        ->and($user->can('view', $budgetActualAccount))->toBeTrue();
})->with([
    'system administrator' => UserType::Admin,
    'company administrator' => UserType::CompanyAdmin,
    'regular user' => UserType::User,
]);

test('only administrators can manage budget actual accounts', function (UserType $userType, bool $canManage) {
    $user = User::factory()->create(['user_type' => $userType]);
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();

    expect($user->can('create', BudgetActualAccount::class))->toBe($canManage)
        ->and($user->can('update', $budgetActualAccount))->toBe($canManage);
})->with([
    'system administrator' => [UserType::Admin, true],
    'company administrator' => [UserType::CompanyAdmin, true],
    'regular user' => [UserType::User, false],
]);

test('administrators cannot manage another organization budget actual account', function () {
    $user = User::factory()->companyAdmin()->create();
    $otherBudgetActualAccount = BudgetActualAccount::factory()->for(Organization::factory())->create();

    expect($user->can('update', $otherBudgetActualAccount))->toBeFalse();
});
