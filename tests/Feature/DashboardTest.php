<?php

use App\Enums\UserType;
use App\Models\Department;
use App\Models\FixedAsset;
use App\Models\ManagementAccount;
use App\Models\MonthlyAmount;
use App\Models\Organization;
use App\Models\User;

test('dashboard displays statistics for the current organization', function () {
    $organization = Organization::factory()->create(['name' => 'テスト株式会社']);
    $user = User::factory()->for($organization)->create(['name' => '非表示ユーザー']);
    $department = Department::factory()->for($organization)->create();
    Department::factory()->for($organization)->inactive()->create();
    $managementAccount = ManagementAccount::factory()->for($organization)->create();
    ManagementAccount::factory()->for($organization)->inactive()->create();

    MonthlyAmount::factory()->count(2)->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'type' => 'budget',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'type' => 'actual',
    ]);
    MonthlyAmount::factory()->create();
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'asset_category' => 'machinery_equipment',
        'depreciation_method' => 'straight_line',
        'acquisition_cost' => '120000.00',
        'accumulated_depreciation' => '22000.00',
        'status' => 'held',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'asset_category' => 'furniture_fixture',
        'depreciation_method' => 'straight_line',
        'acquisition_cost' => '80000.00',
        'accumulated_depreciation' => '30000.00',
        'status' => 'held',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'acquisition_cost' => '1000000.00',
        'accumulated_depreciation' => '100000.00',
        'status' => 'sold',
    ]);
    FixedAsset::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('organization', function (Organization $dashboardOrganization) use ($organization): bool {
            return $dashboardOrganization->is($organization)
                && $dashboardOrganization->budget_records_count === 2
                && $dashboardOrganization->actual_records_count === 1
                && $dashboardOrganization->active_departments_count === 1
                && $dashboardOrganization->active_management_accounts_count === 1;
        })
        ->assertViewHas('fixedAssetSummary', [
            'count' => 2,
            'acquisitionCost' => '200000.00',
            'bookValue' => '148000.00',
        ])
        ->assertSeeText('テスト株式会社')
        ->assertSeeText('予算レコード')
        ->assertSeeText('実績レコード')
        ->assertSeeText('固定資産管理台帳')
        ->assertSeeText('保有中固定資産')
        ->assertSeeText('200,000.00')
        ->assertSeeText('148,000.00')
        ->assertDontSee('href="'.route('departments.index').'"', false)
        ->assertDontSee('href="'.route('management-accounts.index').'"', false)
        ->assertDontSeeText('管理メニュー')
        ->assertDontSeeText('ログインユーザー');
});

test('master links are displayed on the dashboard only to master administrators', function (UserType $userType) {
    $user = User::factory()->create(['user_type' => $userType]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('管理メニュー')
        ->assertSee('href="'.route('departments.index').'"', false)
        ->assertSee('href="'.route('management-accounts.index').'"', false);
})->with([
    'system administrator' => UserType::Admin,
    'company administrator' => UserType::CompanyAdmin,
]);

test('global navigation does not display master links', function () {
    $user = User::factory()->companyAdmin()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertDontSee('href="'.route('departments.index').'"', false)
        ->assertDontSee('href="'.route('management-accounts.index').'"', false);
});

test('guests are redirected from the dashboard to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
