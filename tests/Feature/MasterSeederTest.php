<?php

use App\Models\Department;
use App\Models\ManagementAccount;
use App\Models\MonthlyAmount;
use App\Models\Organization;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ManagementAccountSeeder;

test('master seeders insert the complete data set for every organization', function () {
    $organizations = Organization::factory()->count(2)->create();

    $this->seed([
        DepartmentSeeder::class,
        ManagementAccountSeeder::class,
    ]);

    $this->assertDatabaseCount('departments', 30);
    $this->assertDatabaseCount('management_accounts', 122);

    foreach ($organizations as $organization) {
        $this->assertDatabaseHas('departments', [
            'organization_id' => $organization->id,
            'code' => 'D240',
            'name' => 'CS管理部 管理G',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('management_accounts', [
            'organization_id' => $organization->id,
            'code' => '1500',
        ]);
        $this->assertDatabaseHas('management_accounts', [
            'organization_id' => $organization->id,
            'code' => '1510',
            'name' => '建物',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('management_accounts', [
            'organization_id' => $organization->id,
            'code' => '1511',
            'name' => '構築物',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('management_accounts', [
            'organization_id' => $organization->id,
            'code' => '1530',
            'name' => '工具器具備品',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('management_accounts', [
            'organization_id' => $organization->id,
            'code' => '1590',
            'name' => '建設仮勘定',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('management_accounts', [
            'organization_id' => $organization->id,
            'code' => '1610',
            'name' => 'ソフトウェア仮勘定',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('management_accounts', [
            'organization_id' => $organization->id,
            'code' => '7300',
            'name' => '貸倒関連費用',
            'account_type' => 'expense',
            'is_active' => true,
        ]);
    }
});

test('master seeders can be rerun without creating duplicates', function () {
    Organization::factory()->create();

    $this->seed();
    $this->seed();

    $this->assertDatabaseCount('departments', 30);
    $this->assertDatabaseCount('management_accounts', 122);
});

test('management account seeder safely retires the obsolete fixed asset aggregate account', function () {
    $unusedOrganization = Organization::factory()->create();
    $referencedOrganization = Organization::factory()->create();
    $referencedDepartment = Department::factory()->for($referencedOrganization)->create();

    ManagementAccount::factory()->for($unusedOrganization)->create([
        'code' => '1500',
        'name' => '有形固定資産',
        'account_type' => 'asset',
        'is_active' => true,
    ]);
    $referencedManagementAccount = ManagementAccount::factory()->for($referencedOrganization)->create([
        'code' => '1500',
        'name' => '有形固定資産',
        'account_type' => 'asset',
        'is_active' => true,
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $referencedOrganization->id,
        'department_id' => $referencedDepartment->id,
        'management_account_id' => $referencedManagementAccount->id,
    ]);

    $this->seed(ManagementAccountSeeder::class);

    $this->assertDatabaseMissing('management_accounts', [
        'organization_id' => $unusedOrganization->id,
        'code' => '1500',
    ]);
    $this->assertDatabaseHas('management_accounts', [
        'id' => $referencedManagementAccount->id,
        'organization_id' => $referencedOrganization->id,
        'code' => '1500',
        'is_active' => false,
    ]);
    $this->assertDatabaseHas('monthly_amounts', [
        'organization_id' => $referencedOrganization->id,
        'management_account_id' => $referencedManagementAccount->id,
    ]);
});

test('database seeding creates initial tenant data and master records', function () {
    $this->seed();

    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('organizations', 1);
    $this->assertDatabaseCount('departments', 15);
    $this->assertDatabaseCount('management_accounts', 61);
    $this->assertDatabaseCount('monthly_amounts', 20);
    $this->assertDatabaseCount('fixed_assets', 3);
});
