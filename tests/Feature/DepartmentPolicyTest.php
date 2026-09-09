<?php

use App\Models\Department;
use App\Models\Organization;
use App\Models\User;

test('all user types can view their organization departments', function (string $userType) {
    $user = User::factory()->create(['user_type' => $userType]);
    $department = Department::factory()->for($user->organization)->create();

    expect($user->can('viewAny', Department::class))->toBeTrue()
        ->and($user->can('view', $department))->toBeTrue();
})->with([
    'system administrator' => 'admin',
    'company administrator' => 'company_admin',
    'regular user' => 'user',
]);

test('only administrators can manage departments', function (string $userType, bool $canManage) {
    $user = User::factory()->create(['user_type' => $userType]);
    $department = Department::factory()->for($user->organization)->create();

    expect($user->can('create', Department::class))->toBe($canManage)
        ->and($user->can('update', $department))->toBe($canManage);
})->with([
    'system administrator' => ['admin', true],
    'company administrator' => ['company_admin', true],
    'regular user' => ['user', false],
]);

test('administrators cannot manage another organization department', function () {
    $user = User::factory()->companyAdmin()->create();
    $otherDepartment = Department::factory()->for(Organization::factory())->create();

    expect($user->can('update', $otherDepartment))->toBeFalse();
});
