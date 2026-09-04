<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('ユーザーID')
        ->assertDontSee('メールアドレス');
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'login_id' => ' Test.User ',
        'name' => 'Test User',
        'organization_name' => 'テスト株式会社',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('login_id', 'test.user')->firstOrFail();

    expect($user->email)->toBeNull();
    $this->assertDatabaseHas('organizations', [
        'id' => $user->organization_id,
        'name' => 'テスト株式会社',
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('favicon.svg')
        ->assertSee('テスト株式会社');
});

test('login ids are unique after normalization', function () {
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
