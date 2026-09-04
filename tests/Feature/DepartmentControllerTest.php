<?php

use App\Models\Department;
use App\Models\Organization;
use App\Models\User;

test('users can create and view departments in their organization', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('departments.create'))
        ->assertOk()
        ->assertSee('部門を登録');

    $this->actingAs($user)
        ->post(route('departments.store'), [
            'code' => ' d001 ',
            'name' => ' 開発部 ',
        ])
        ->assertRedirect(route('departments.index'));

    $this->assertDatabaseHas('departments', [
        'organization_id' => $user->organization_id,
        'code' => 'D001',
        'name' => '開発部',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('departments.index'))
        ->assertOk()
        ->assertSee('D001')
        ->assertSee('開発部');
});

test('users can update and disable their departments', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->put(route('departments.update', $department), [
            'code' => 'D010',
            'name' => '管理部',
        ])
        ->assertRedirect(route('departments.index'));

    $this->actingAs($user)
        ->patch(route('departments.status', $department))
        ->assertRedirect();

    $department->refresh();

    expect($department->code)->toBe('D010');
    expect($department->name)->toBe('管理部');
    expect($department->is_active)->toBeFalse();
});

test('departments from another organization return 404', function () {
    $user = User::factory()->create();
    $otherDepartment = Department::factory()
        ->for(Organization::factory())
        ->create();

    $this->actingAs($user)
        ->get(route('departments.edit', $otherDepartment))
        ->assertNotFound();

    $this->actingAs($user)
        ->patch(route('departments.status', $otherDepartment))
        ->assertNotFound();
});
