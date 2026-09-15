<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;

test('registration screen can be rendered', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('ユーザーID')
        ->assertSee('所属部門コード（任意）')
        ->assertSee('所属部門名（任意）')
        ->assertDontSee('メールアドレス');
});

test('new users can register', function () {
    $company = Company::factory()->create(['code' => Company::DEFAULT_CODE]);

    $response = $this->post('/register', [
        'login_id' => ' Test.User ',
        'name' => 'Test User',
        'organization_name' => 'テスト株式会社',
        'department_code' => ' d120 ',
        'department_name' => ' 人事部 ',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('login_id', 'test.user')->firstOrFail();

    expect($user->email)->toBeNull();
    expect($user->company_id)->toBe($company->id);
    expect($user->user_type)->toBe(UserType::User);
    expect($user->department)->toBeInstanceOf(Department::class);
    expect($user->department->code)->toBe('D120');
    expect($user->department->name)->toBe('人事部');
    $this->assertDatabaseHas('organizations', [
        'id' => $user->organization_id,
        'company_id' => $company->id,
        'name' => 'テスト株式会社',
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('mark.webp')
        ->assertSee('テスト株式会社');
});

test('department code and name must be entered together during registration', function () {
    Company::factory()->create(['code' => Company::DEFAULT_CODE]);

    $this->post('/register', [
        'login_id' => 'test.user',
        'name' => 'Test User',
        'organization_name' => 'テスト株式会社',
        'department_code' => 'D120',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('department_name');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('login ids are unique after normalization', function () {
    Company::factory()->create(['code' => Company::DEFAULT_CODE]);
    User::factory()->create(['login_id' => 'test.user']);

    $this->post('/register', [
        'login_id' => 'TEST.USER',
        'name' => 'Another User',
        'organization_name' => '別会社',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('login_id');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});
