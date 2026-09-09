<?php

use App\Models\Account;
use App\Models\Organization;
use App\Models\User;

test('all user types can view their organization accounts', function (string $userType) {
    $user = User::factory()->create(['user_type' => $userType]);
    $account = Account::factory()->for($user->organization)->create();

    expect($user->can('viewAny', Account::class))->toBeTrue()
        ->and($user->can('view', $account))->toBeTrue();
})->with([
    'system administrator' => 'admin',
    'company administrator' => 'company_admin',
    'regular user' => 'user',
]);

test('only administrators can manage accounts', function (string $userType, bool $canManage) {
    $user = User::factory()->create(['user_type' => $userType]);
    $account = Account::factory()->for($user->organization)->create();

    expect($user->can('create', Account::class))->toBe($canManage)
        ->and($user->can('update', $account))->toBe($canManage);
})->with([
    'system administrator' => ['admin', true],
    'company administrator' => ['company_admin', true],
    'regular user' => ['user', false],
]);

test('administrators cannot manage another organization account', function () {
    $user = User::factory()->companyAdmin()->create();
    $otherAccount = Account::factory()->for(Organization::factory())->create();

    expect($user->can('update', $otherAccount))->toBeFalse();
});
