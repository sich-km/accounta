<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\InitialTenantSeeder;
use Illuminate\Support\Facades\Hash;

test('database seeder creates the initial tenant admin and default masters once', function () {
    $this->seed();

    $company = Company::query()
        ->where('code', Company::DEFAULT_CODE)
        ->firstOrFail();
    $organization = $company->organizations()->firstOrFail();
    $admin = User::query()->where('login_id', 'admin')->firstOrFail();
    $companyAdmin = User::query()->where('login_id', 'company_admin')->firstOrFail();
    $user = User::query()->where('login_id', 'user1')->firstOrFail();

    expect($company->name)->toBe('KM');
    expect($company->fiscal_year_start_month)->toBe(1);
    expect($organization->name)->toBe('KM');
    expect($admin->company_id)->toBe($company->id);
    expect($admin->organization_id)->toBe($organization->id);
    expect($admin->user_type)->toBe(UserType::Admin);
    expect($admin->name)->toBe('admin');
    expect(Hash::check('password', $admin->password))->toBeFalse();
    expect($companyAdmin->company_id)->toBe($company->id);
    expect($companyAdmin->organization_id)->toBe($organization->id);
    expect($companyAdmin->user_type)->toBe(UserType::CompanyAdmin);
    expect($companyAdmin->name)->toBe('company_admin');
    expect(Hash::check('password', $companyAdmin->password))->toBeFalse();
    expect($user->company_id)->toBe($company->id);
    expect($user->organization_id)->toBe($organization->id);
    expect($user->user_type)->toBe(UserType::User);
    expect($user->name)->toBe('user1');
    expect(Hash::check('password', $user->password))->toBeFalse();
    $this->assertDatabaseHas('departments', [
        'organization_id' => $organization->id,
        'code' => 'D100',
        'name' => '経営企画部',
    ]);
    $this->assertDatabaseHas('management_accounts', [
        'organization_id' => $organization->id,
        'code' => '1000',
        'name' => '現金及び預金',
    ]);
    $csDepartment = $organization->departments()->where('code', 'D210')->firstOrFail();
    $domesticSalesDepartment = $organization->departments()->where('code', 'D410')->firstOrFail();
    $productSales = $organization->managementAccounts()->where('code', '4000')->firstOrFail();

    $this->assertDatabaseHas('monthly_amounts', [
        'organization_id' => $organization->id,
        'department_id' => $domesticSalesDepartment->id,
        'management_account_id' => $productSales->id,
        'period' => '2026-04-01',
        'type' => 'budget',
        'amount' => '8000000.00',
    ]);
    $this->assertDatabaseHas('monthly_amounts', [
        'organization_id' => $organization->id,
        'department_id' => $domesticSalesDepartment->id,
        'management_account_id' => $productSales->id,
        'period' => '2026-04-01',
        'type' => 'actual',
        'amount' => '8200000.00',
    ]);
    $this->assertDatabaseHas('fixed_assets', [
        'organization_id' => $organization->id,
        'department_id' => $csDepartment->id,
        'asset_code' => 'FA-CS-001',
        'asset_name' => '顧客対応用ノートパソコン',
    ]);
    expect($organization->monthlyAmounts()->count())->toBe(20);
    expect($organization->fixedAssets()->count())->toBe(3);
    expect($organization->fixedAssets()->where('department_id', '!=', $csDepartment->id)->doesntExist())->toBeTrue();

    $departmentCount = $organization->departments()->count();
    $managementAccountCount = $organization->managementAccounts()->count();
    $monthlyAmountCount = $organization->monthlyAmounts()->count();
    $fixedAssetCount = $organization->fixedAssets()->count();
    $adminPasswordHash = $admin->password;
    $companyAdminPasswordHash = $companyAdmin->password;
    $userPasswordHash = $user->password;

    $this->seed();

    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseCount('organizations', 1);
    $this->assertDatabaseCount('users', 3);
    expect($organization->departments()->count())->toBe($departmentCount);
    expect($organization->managementAccounts()->count())->toBe($managementAccountCount);
    expect($organization->monthlyAmounts()->count())->toBe($monthlyAmountCount);
    expect($organization->fixedAssets()->count())->toBe($fixedAssetCount);
    expect($admin->fresh()->password)->toBe($adminPasswordHash);
    expect($companyAdmin->fresh()->password)->toBe($companyAdminPasswordHash);
    expect($user->fresh()->password)->toBe($userPasswordHash);
});

test('initial tenant seeder preserves an existing user password and role separation', function () {
    $company = Company::factory()->create(['code' => Company::DEFAULT_CODE]);
    $organization = $company->organizations()->create([
        'name' => 'KM',
        'type' => 'company',
    ]);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'organization_id' => $organization->id,
        'user_type' => UserType::User,
        'login_id' => 'user1',
        'name' => 'ユーザー1',
        'password' => Hash::make('existing-secret'),
    ]);
    $passwordHash = $user->password;

    $this->seed(InitialTenantSeeder::class);

    $user->refresh();
    $admin = User::query()->where('login_id', 'admin')->firstOrFail();
    $companyAdmin = User::query()->where('login_id', 'company_admin')->firstOrFail();

    expect($user->login_id)->toBe('user1');
    expect($user->name)->toBe('user1');
    expect($user->user_type)->toBe(UserType::User);
    expect($user->password)->toBe($passwordHash);
    expect($admin->user_type)->toBe(UserType::Admin);
    expect($companyAdmin->user_type)->toBe(UserType::CompanyAdmin);
    $this->assertDatabaseCount('users', 3);
});
