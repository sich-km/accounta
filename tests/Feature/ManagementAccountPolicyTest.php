<?php

use App\Enums\UserType;
use App\Models\ManagementAccount;
use App\Models\Organization;
use App\Models\User;

test('all user types can view their organization management accounts', function (UserType $userType) {
    $user = User::factory()->create(['user_type' => $userType]);
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create();

    expect($user->can('viewAny', ManagementAccount::class))->toBeTrue()
        ->and($user->can('view', $managementAccount))->toBeTrue();
})->with([
    'system administrator' => UserType::Admin,
    'company administrator' => UserType::CompanyAdmin,
    'regular user' => UserType::User,
]);

test('only administrators can manage management accounts', function (UserType $userType, bool $canManage) {
    $user = User::factory()->create(['user_type' => $userType]);
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create();

    expect($user->can('create', ManagementAccount::class))->toBe($canManage)
        ->and($user->can('update', $managementAccount))->toBe($canManage);
})->with([
    'system administrator' => [UserType::Admin, true],
    'company administrator' => [UserType::CompanyAdmin, true],
    'regular user' => [UserType::User, false],
]);

test('administrators cannot manage another organization management account', function () {
    $user = User::factory()->companyAdmin()->create();
    $otherManagementAccount = ManagementAccount::factory()->for(Organization::factory())->create();

    expect($user->can('update', $otherManagementAccount))->toBeFalse();
});
