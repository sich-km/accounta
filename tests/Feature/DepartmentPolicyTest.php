<?php

use App\Enums\UserType;
use App\Models\Department;
use App\Models\Organization;
use App\Models\User;

test('all user types can view their organization departments', function (UserType $userType) {
    $user = User::factory()->create(['user_type' => $userType]);
    $department = Department::factory()->for($user->organization)->create();

    expect($user->can('viewAny', Department::class))->toBeTrue()
        ->and($user->can('view', $department))->toBeTrue();
})->with([
    'system administrator' => UserType::Admin,
    'company administrator' => UserType::CompanyAdmin,
    'regular user' => UserType::User,
]);

test('only administrators can manage departments', function (UserType $userType, bool $canManage) {
    $user = User::factory()->create(['user_type' => $userType]);
    $department = Department::factory()->for($user->organization)->create();

    expect($user->can('create', Department::class))->toBe($canManage)
        ->and($user->can('update', $department))->toBe($canManage);
})->with([
    'system administrator' => [UserType::Admin, true],
    'company administrator' => [UserType::CompanyAdmin, true],
    'regular user' => [UserType::User, false],
]);

test('administrators cannot manage another organization department', function () {
    $user = User::factory()->companyAdmin()->create();
    $otherDepartment = Department::factory()->for(Organization::factory())->create();

    expect($user->can('update', $otherDepartment))->toBeFalse();
});
