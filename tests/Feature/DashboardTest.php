<?php

use App\Models\Account;
use App\Models\Department;
use App\Models\MonthlyAmount;
use App\Models\Organization;
use App\Models\User;

test('dashboard displays statistics for the current organization', function () {
    $organization = Organization::factory()->create(['name' => 'テスト株式会社']);
    $user = User::factory()->for($organization)->create(['name' => '非表示ユーザー']);
    $department = Department::factory()->for($organization)->create();
    Department::factory()->for($organization)->inactive()->create();
    $account = Account::factory()->for($organization)->create();
    Account::factory()->for($organization)->inactive()->create();

    MonthlyAmount::factory()->count(2)->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'account_id' => $account->id,
        'type' => 'budget',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'account_id' => $account->id,
        'type' => 'actual',
    ]);
    MonthlyAmount::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('organization', function (Organization $dashboardOrganization) use ($organization): bool {
            return $dashboardOrganization->is($organization)
                && $dashboardOrganization->budget_records_count === 2
                && $dashboardOrganization->actual_records_count === 1
                && $dashboardOrganization->active_departments_count === 1
                && $dashboardOrganization->active_accounts_count === 1;
        })
        ->assertSeeText('テスト株式会社')
        ->assertSeeText('予算レコード')
        ->assertSeeText('実績レコード')
        ->assertDontSeeText('ログインユーザー');
});

test('guests are redirected from the dashboard to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
