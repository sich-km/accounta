<?php

use App\Models\BudgetActualAccount;
use App\Models\Organization;
use App\Models\User;

test('company administrators can create and view budget actual accounts in their organization', function () {
    $user = User::factory()->companyAdmin()->create();

    $this->actingAs($user)
        ->get(route('budget-actual-accounts.create'))
        ->assertOk()
        ->assertSee('予実管理科目を登録');

    $this->actingAs($user)
        ->post(route('budget-actual-accounts.store'), [
            'code' => ' a001 ',
            'name' => ' 売上高 ',
            'account_type' => 'revenue',
        ])
        ->assertRedirect(route('budget-actual-accounts.index'));

    $this->assertDatabaseHas('budget_actual_accounts', [
        'organization_id' => $user->organization_id,
        'code' => 'A001',
        'name' => '売上高',
        'account_type' => 'revenue',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('budget-actual-accounts.index'))
        ->assertOk()
        ->assertSee('A001')
        ->assertSee('売上高')
        ->assertSee('href="'.route('management.index').'"', false)
        ->assertSeeText('管理へ戻る');
});

test('system administrators can update and disable their budget actual accounts', function () {
    $user = User::factory()->admin()->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->put(route('budget-actual-accounts.update', $budgetActualAccount), [
            'code' => 'A010',
            'name' => '外注費',
            'account_type' => 'expense',
        ])
        ->assertRedirect(route('budget-actual-accounts.index'));

    $this->actingAs($user)
        ->patch(route('budget-actual-accounts.status', $budgetActualAccount))
        ->assertRedirect();

    $budgetActualAccount->refresh();

    expect($budgetActualAccount->code)->toBe('A010');
    expect($budgetActualAccount->name)->toBe('外注費');
    expect($budgetActualAccount->account_type)->toBe('expense');
    expect($budgetActualAccount->is_active)->toBeFalse();
});

test('budget actual accounts from another organization return 404', function () {
    $user = User::factory()->companyAdmin()->create();
    $otherBudgetActualAccount = BudgetActualAccount::factory()
        ->for(Organization::factory())
        ->create();

    $this->actingAs($user)
        ->get(route('budget-actual-accounts.edit', $otherBudgetActualAccount))
        ->assertNotFound();

    $this->actingAs($user)
        ->patch(route('budget-actual-accounts.status', $otherBudgetActualAccount))
        ->assertNotFound();
});

test('regular users can only view budget actual accounts without management controls', function () {
    $user = User::factory()->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create([
        'code' => 'A100',
        'name' => '閲覧専用科目',
        'account_type' => 'expense',
    ]);

    $this->actingAs($user)
        ->get(route('budget-actual-accounts.index'))
        ->assertOk()
        ->assertSee('A100')
        ->assertSee('閲覧専用科目')
        ->assertDontSee('新規登録')
        ->assertDontSee('編集')
        ->assertDontSee('無効化')
        ->assertDontSeeText('管理へ戻る');

    $this->actingAs($user)
        ->get(route('budget-actual-accounts.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('budget-actual-accounts.store'), [
            'code' => 'A200',
            'name' => '登録不可科目',
            'account_type' => 'expense',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('budget-actual-accounts.edit', $budgetActualAccount))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('budget-actual-accounts.update', $budgetActualAccount), [
            'code' => 'A101',
            'name' => '変更不可科目',
            'account_type' => 'expense',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('budget-actual-accounts.status', $budgetActualAccount))
        ->assertForbidden();

    $this->assertDatabaseMissing('budget_actual_accounts', ['code' => 'A200']);
    expect($budgetActualAccount->fresh()->only(['code', 'name', 'account_type', 'is_active']))->toBe([
        'code' => 'A100',
        'name' => '閲覧専用科目',
        'account_type' => 'expense',
        'is_active' => true,
    ]);
});
