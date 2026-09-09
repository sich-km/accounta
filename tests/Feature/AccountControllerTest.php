<?php

use App\Models\Account;
use App\Models\Organization;
use App\Models\User;

test('company administrators can create and view accounts in their organization', function () {
    $user = User::factory()->companyAdmin()->create();

    $this->actingAs($user)
        ->get(route('accounts.create'))
        ->assertOk()
        ->assertSee('勘定科目を登録');

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'code' => ' a001 ',
            'name' => ' 売上高 ',
            'account_type' => 'revenue',
        ])
        ->assertRedirect(route('accounts.index'));

    $this->assertDatabaseHas('accounts', [
        'organization_id' => $user->organization_id,
        'code' => 'A001',
        'name' => '売上高',
        'account_type' => 'revenue',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertSee('A001')
        ->assertSee('売上高');
});

test('system administrators can update and disable their accounts', function () {
    $user = User::factory()->admin()->create();
    $account = Account::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->put(route('accounts.update', $account), [
            'code' => 'A010',
            'name' => '外注費',
            'account_type' => 'expense',
        ])
        ->assertRedirect(route('accounts.index'));

    $this->actingAs($user)
        ->patch(route('accounts.status', $account))
        ->assertRedirect();

    $account->refresh();

    expect($account->code)->toBe('A010');
    expect($account->name)->toBe('外注費');
    expect($account->account_type)->toBe('expense');
    expect($account->is_active)->toBeFalse();
});

test('accounts from another organization return 404', function () {
    $user = User::factory()->companyAdmin()->create();
    $otherAccount = Account::factory()
        ->for(Organization::factory())
        ->create();

    $this->actingAs($user)
        ->get(route('accounts.edit', $otherAccount))
        ->assertNotFound();

    $this->actingAs($user)
        ->patch(route('accounts.status', $otherAccount))
        ->assertNotFound();
});

test('regular users can only view accounts without management controls', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user->organization)->create([
        'code' => 'A100',
        'name' => '閲覧専用科目',
        'account_type' => 'expense',
    ]);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertSee('A100')
        ->assertSee('閲覧専用科目')
        ->assertDontSee('新規登録')
        ->assertDontSee('編集')
        ->assertDontSee('無効化');

    $this->actingAs($user)
        ->get(route('accounts.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'code' => 'A200',
            'name' => '登録不可科目',
            'account_type' => 'expense',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('accounts.edit', $account))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('accounts.update', $account), [
            'code' => 'A101',
            'name' => '変更不可科目',
            'account_type' => 'expense',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('accounts.status', $account))
        ->assertForbidden();

    $this->assertDatabaseMissing('accounts', ['code' => 'A200']);
    expect($account->fresh()->only(['code', 'name', 'account_type', 'is_active']))->toBe([
        'code' => 'A100',
        'name' => '閲覧専用科目',
        'account_type' => 'expense',
        'is_active' => true,
    ]);
});
