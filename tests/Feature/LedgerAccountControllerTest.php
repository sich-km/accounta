<?php

use App\Enums\JournalSide;
use App\Enums\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\Organization;
use App\Models\User;

test('authenticated users see only their organization ledger accounts', function () {
    $user = User::factory()->create();
    $ownAccount = LedgerAccount::factory()->for($user->organization)->create(['name' => '自組織現金']);
    $otherAccount = LedgerAccount::factory()->create(['name' => '他組織現金']);

    $this->actingAs($user)->get(route('ledger-accounts.index'))
        ->assertOk()
        ->assertSeeText($ownAccount->name)
        ->assertDontSeeText($otherAccount->name)
        ->assertDontSeeText('管理へ戻る');
});

test('an administrator can create a normalized ledger account', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('ledger-accounts.index'))
        ->assertOk()
        ->assertSee('href="'.route('management.index').'"', false)
        ->assertSeeText('管理へ戻る');

    $this->actingAs($user)->post(route('ledger-accounts.store'), [
        'organization_id' => Organization::factory()->create()->id,
        'code' => ' cash-01 ',
        'name' => ' 現金 ',
        'account_type' => LedgerAccountType::Asset->value,
        'normal_balance' => JournalSide::Debit->value,
    ])->assertRedirect(route('ledger-accounts.index'));

    $this->assertDatabaseHas('ledger_accounts', [
        'organization_id' => $user->organization_id,
        'code' => 'CASH-01',
        'name' => '現金',
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);
});

test('a regular user cannot create or update ledger accounts', function () {
    $user = User::factory()->create();
    $ledgerAccount = LedgerAccount::factory()->for($user->organization)->create();
    $payload = [
        'code' => 'CASH',
        'name' => '現金',
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ];

    $this->actingAs($user)->post(route('ledger-accounts.store'), $payload)->assertForbidden();
    $this->actingAs($user)->put(route('ledger-accounts.update', $ledgerAccount), $payload)->assertForbidden();
});

test('ledger account codes are unique within an organization', function () {
    $user = User::factory()->companyAdmin()->create();
    LedgerAccount::factory()->for($user->organization)->create(['code' => '1000']);

    $this->actingAs($user)->post(route('ledger-accounts.store'), [
        'code' => '1000',
        'name' => '重複科目',
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ])->assertSessionHasErrors('code');

    $this->assertDatabaseMissing('ledger_accounts', ['organization_id' => $user->organization_id, 'name' => '重複科目']);
});

test('another organization ledger account returns 404 for management routes', function () {
    $user = User::factory()->admin()->create();
    $otherAccount = LedgerAccount::factory()->create();

    $this->actingAs($user)->get(route('ledger-accounts.edit', $otherAccount))->assertNotFound();
    $this->actingAs($user)->patch(route('ledger-accounts.status', $otherAccount))->assertNotFound();
});

test('guests cannot access ledger account routes', function () {
    $this->get(route('ledger-accounts.index'))->assertRedirect(route('login'));
    $this->post(route('ledger-accounts.store'), [])->assertRedirect(route('login'));
});
