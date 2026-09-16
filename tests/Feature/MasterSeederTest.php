<?php

use App\Models\BudgetActualAccount;
use App\Models\BudgetActualEntry;
use App\Models\Department;
use App\Models\Organization;
use Database\Seeders\BudgetActualAccountSeeder;
use Database\Seeders\DepartmentSeeder;

test('master seeders insert the complete data set for every organization', function () {
    $organizations = Organization::factory()->count(2)->create();

    $this->seed([
        DepartmentSeeder::class,
        BudgetActualAccountSeeder::class,
    ]);

    $this->assertDatabaseCount('departments', 30);
    $this->assertDatabaseCount('budget_actual_accounts', 122);

    foreach ($organizations as $organization) {
        $this->assertDatabaseHas('departments', [
            'organization_id' => $organization->id,
            'code' => 'D240',
            'name' => 'CS管理部 管理G',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('budget_actual_accounts', [
            'organization_id' => $organization->id,
            'code' => '1500',
        ]);
        $this->assertDatabaseHas('budget_actual_accounts', [
            'organization_id' => $organization->id,
            'code' => '1510',
            'name' => '建物',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('budget_actual_accounts', [
            'organization_id' => $organization->id,
            'code' => '1511',
            'name' => '構築物',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('budget_actual_accounts', [
            'organization_id' => $organization->id,
            'code' => '1530',
            'name' => '工具器具備品',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('budget_actual_accounts', [
            'organization_id' => $organization->id,
            'code' => '1590',
            'name' => '建設仮勘定',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('budget_actual_accounts', [
            'organization_id' => $organization->id,
            'code' => '1610',
            'name' => 'ソフトウェア仮勘定',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('budget_actual_accounts', [
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
    $this->assertDatabaseCount('budget_actual_accounts', 122);
});

test('budget actual account seeder safely retires the obsolete fixed asset aggregate account', function () {
    $unusedOrganization = Organization::factory()->create();
    $referencedOrganization = Organization::factory()->create();
    $referencedDepartment = Department::factory()->for($referencedOrganization)->create();

    BudgetActualAccount::factory()->for($unusedOrganization)->create([
        'code' => '1500',
        'name' => '有形固定資産',
        'account_type' => 'asset',
        'is_active' => true,
    ]);
    $referencedBudgetActualAccount = BudgetActualAccount::factory()->for($referencedOrganization)->create([
        'code' => '1500',
        'name' => '有形固定資産',
        'account_type' => 'asset',
        'is_active' => true,
    ]);
    BudgetActualEntry::factory()->create([
        'organization_id' => $referencedOrganization->id,
        'department_id' => $referencedDepartment->id,
        'budget_actual_account_id' => $referencedBudgetActualAccount->id,
    ]);

    $this->seed(BudgetActualAccountSeeder::class);

    $this->assertDatabaseMissing('budget_actual_accounts', [
        'organization_id' => $unusedOrganization->id,
        'code' => '1500',
    ]);
    $this->assertDatabaseHas('budget_actual_accounts', [
        'id' => $referencedBudgetActualAccount->id,
        'organization_id' => $referencedOrganization->id,
        'code' => '1500',
        'is_active' => false,
    ]);
    $this->assertDatabaseHas('budget_actual_entries', [
        'organization_id' => $referencedOrganization->id,
        'budget_actual_account_id' => $referencedBudgetActualAccount->id,
    ]);
});

test('database seeding creates initial tenant data and master records', function () {
    $this->seed();

    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('organizations', 1);
    $this->assertDatabaseCount('departments', 15);
    $this->assertDatabaseCount('budget_actual_accounts', 61);
    $this->assertDatabaseCount('budget_actual_entries', 20);
    $this->assertDatabaseCount('fixed_assets', 3);
});
