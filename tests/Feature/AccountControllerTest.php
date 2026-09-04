<?php

use App\Models\Account;
use App\Models\Organization;
use App\Models\User;

test('users can create and view accounts in their organization', function () {
    $user = User::factory()->create();

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

test('users can update and disable their accounts', function () {
    $user = User::factory()->create();
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
    $user = User::factory()->create();
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
