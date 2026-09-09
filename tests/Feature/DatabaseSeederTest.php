<?php

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
    expect($admin->user_type)->toBe('admin');
    expect($admin->name)->toBe('admin');
    expect(Hash::check('password', $admin->password))->toBeFalse();
    expect($companyAdmin->company_id)->toBe($company->id);
    expect($companyAdmin->organization_id)->toBe($organization->id);
    expect($companyAdmin->user_type)->toBe('company_admin');
    expect($companyAdmin->name)->toBe('company_admin');
    expect(Hash::check('password', $companyAdmin->password))->toBeFalse();
    expect($user->company_id)->toBe($company->id);
    expect($user->organization_id)->toBe($organization->id);
    expect($user->user_type)->toBe('user');
    expect($user->name)->toBe('user1');
    expect(Hash::check('password', $user->password))->toBeFalse();
    $this->assertDatabaseHas('departments', [
        'organization_id' => $organization->id,
        'code' => 'D100',
        'name' => '経営企画部',
    ]);
    $this->assertDatabaseHas('accounts', [
        'organization_id' => $organization->id,
        'code' => '1000',
        'name' => '現金及び預金',
    ]);

    $departmentCount = $organization->departments()->count();
    $accountCount = $organization->accounts()->count();
    $adminPasswordHash = $admin->password;
    $companyAdminPasswordHash = $companyAdmin->password;
    $userPasswordHash = $user->password;

    $this->seed();

    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseCount('organizations', 1);
    $this->assertDatabaseCount('users', 3);
    expect($organization->departments()->count())->toBe($departmentCount);
    expect($organization->accounts()->count())->toBe($accountCount);
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
        'user_type' => 'user',
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
    expect($user->user_type)->toBe('user');
    expect($user->password)->toBe($passwordHash);
    expect($admin->user_type)->toBe('admin');
    expect($companyAdmin->user_type)->toBe('company_admin');
    $this->assertDatabaseCount('users', 3);
});
