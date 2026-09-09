<?php

use App\Models\Department;
use App\Models\Organization;
use App\Models\User;

test('company administrators can create and view departments in their organization', function () {
    $user = User::factory()->companyAdmin()->create();

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

test('system administrators can update and disable their departments', function () {
    $user = User::factory()->admin()->create();
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
    $user = User::factory()->companyAdmin()->create();
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

test('regular users can only view departments without management controls', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create([
        'code' => 'D100',
        'name' => '閲覧専用部門',
    ]);

    $this->actingAs($user)
        ->get(route('departments.index'))
        ->assertOk()
        ->assertSee('D100')
        ->assertSee('閲覧専用部門')
        ->assertDontSee('新規登録')
        ->assertDontSee('編集')
        ->assertDontSee('無効化');

    $this->actingAs($user)
        ->get(route('departments.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('departments.store'), [
            'code' => 'D200',
            'name' => '登録不可部門',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('departments.edit', $department))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('departments.update', $department), [
            'code' => 'D101',
            'name' => '変更不可部門',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('departments.status', $department))
        ->assertForbidden();

    $this->assertDatabaseMissing('departments', ['code' => 'D200']);
    expect($department->fresh()->only(['code', 'name', 'is_active']))->toBe([
        'code' => 'D100',
        'name' => '閲覧専用部門',
        'is_active' => true,
    ]);
});
