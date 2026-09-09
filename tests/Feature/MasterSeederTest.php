<?php

use App\Models\Organization;
use Database\Seeders\AccountSeeder;
use Database\Seeders\DepartmentSeeder;

test('master seeders insert the complete data set for every organization', function () {
    $organizations = Organization::factory()->count(2)->create();

    $this->seed([
        DepartmentSeeder::class,
        AccountSeeder::class,
    ]);

    $this->assertDatabaseCount('departments', 30);
    $this->assertDatabaseCount('accounts', 118);

    foreach ($organizations as $organization) {
        $this->assertDatabaseHas('departments', [
            'organization_id' => $organization->id,
            'code' => 'D240',
            'name' => 'CS管理部 管理G',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('accounts', [
            'organization_id' => $organization->id,
            'code' => '1530',
            'name' => '工具器具備品',
            'account_type' => 'asset',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('accounts', [
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
    $this->assertDatabaseCount('accounts', 118);
});

test('database seeding creates initial tenant data and master records', function () {
    $this->seed();

    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('organizations', 1);
    $this->assertDatabaseCount('departments', 15);
    $this->assertDatabaseCount('accounts', 59);
});
