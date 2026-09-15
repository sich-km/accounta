<?php

use App\Models\ManagementAccount;
use App\Models\Organization;
use App\Models\User;

test('company administrators can create and view management accounts in their organization', function () {
    $user = User::factory()->companyAdmin()->create();

    $this->actingAs($user)
        ->get(route('management-accounts.create'))
        ->assertOk()
        ->assertSee('予実管理科目を登録');

    $this->actingAs($user)
        ->post(route('management-accounts.store'), [
            'code' => ' a001 ',
            'name' => ' 売上高 ',
            'account_type' => 'revenue',
        ])
        ->assertRedirect(route('management-accounts.index'));

    $this->assertDatabaseHas('management_accounts', [
        'organization_id' => $user->organization_id,
        'code' => 'A001',
        'name' => '売上高',
        'account_type' => 'revenue',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('management-accounts.index'))
        ->assertOk()
        ->assertSee('A001')
        ->assertSee('売上高')
        ->assertSee('href="'.route('management.index').'"', false)
        ->assertSeeText('管理へ戻る');
});

test('system administrators can update and disable their management accounts', function () {
    $user = User::factory()->admin()->create();
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->put(route('management-accounts.update', $managementAccount), [
            'code' => 'A010',
            'name' => '外注費',
            'account_type' => 'expense',
        ])
        ->assertRedirect(route('management-accounts.index'));

    $this->actingAs($user)
        ->patch(route('management-accounts.status', $managementAccount))
        ->assertRedirect();

    $managementAccount->refresh();

    expect($managementAccount->code)->toBe('A010');
    expect($managementAccount->name)->toBe('外注費');
    expect($managementAccount->account_type)->toBe('expense');
    expect($managementAccount->is_active)->toBeFalse();
});

test('management accounts from another organization return 404', function () {
    $user = User::factory()->companyAdmin()->create();
    $otherManagementAccount = ManagementAccount::factory()
        ->for(Organization::factory())
        ->create();

    $this->actingAs($user)
        ->get(route('management-accounts.edit', $otherManagementAccount))
        ->assertNotFound();

    $this->actingAs($user)
        ->patch(route('management-accounts.status', $otherManagementAccount))
        ->assertNotFound();
});

test('regular users can only view management accounts without management controls', function () {
    $user = User::factory()->create();
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create([
        'code' => 'A100',
        'name' => '閲覧専用科目',
        'account_type' => 'expense',
    ]);

    $this->actingAs($user)
        ->get(route('management-accounts.index'))
        ->assertOk()
        ->assertSee('A100')
        ->assertSee('閲覧専用科目')
        ->assertDontSee('新規登録')
        ->assertDontSee('編集')
        ->assertDontSee('無効化')
        ->assertDontSeeText('管理へ戻る');

    $this->actingAs($user)
        ->get(route('management-accounts.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('management-accounts.store'), [
            'code' => 'A200',
            'name' => '登録不可科目',
            'account_type' => 'expense',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('management-accounts.edit', $managementAccount))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('management-accounts.update', $managementAccount), [
            'code' => 'A101',
            'name' => '変更不可科目',
            'account_type' => 'expense',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('management-accounts.status', $managementAccount))
        ->assertForbidden();

    $this->assertDatabaseMissing('management_accounts', ['code' => 'A200']);
    expect($managementAccount->fresh()->only(['code', 'name', 'account_type', 'is_active']))->toBe([
        'code' => 'A100',
        'name' => '閲覧専用科目',
        'account_type' => 'expense',
        'is_active' => true,
    ]);
});
