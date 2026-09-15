<?php

use App\Enums\UserType;
use App\Models\LedgerAccount;
use App\Models\Organization;
use App\Models\User;
use App\Policies\LedgerAccountPolicy;

test('all user types can view ledger accounts in their organization', function (UserType $userType) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['user_type' => $userType]);
    $ledgerAccount = LedgerAccount::factory()->for($organization)->create();
    $policy = new LedgerAccountPolicy;

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $ledgerAccount))->toBeTrue();
})->with(UserType::cases());

test('administrators can manage ledger accounts', function (UserType $userType) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['user_type' => $userType]);
    $ledgerAccount = LedgerAccount::factory()->for($organization)->create();
    $policy = new LedgerAccountPolicy;

    expect($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $ledgerAccount))->toBeTrue();
})->with([UserType::Admin, UserType::CompanyAdmin]);

test('regular users cannot manage ledger accounts', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['user_type' => UserType::User]);
    $ledgerAccount = LedgerAccount::factory()->for($organization)->create();
    $policy = new LedgerAccountPolicy;

    expect($policy->create($user))->toBeFalse()
        ->and($policy->update($user, $ledgerAccount))->toBeFalse();
});

test('users cannot view or update another organization ledger account', function () {
    $user = User::factory()->create();
    $ledgerAccount = LedgerAccount::factory()->create();
    $policy = new LedgerAccountPolicy;

    expect($policy->view($user, $ledgerAccount))->toBeFalse()
        ->and($policy->update($user, $ledgerAccount))->toBeFalse()
        ->and($policy->delete($user, $ledgerAccount))->toBeFalse();
});
