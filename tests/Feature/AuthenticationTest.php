<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Accounta')
        ->assertDontSee('予算と実績を、すっきり見える化')
        ->assertSee('logo.webp')
        ->assertSee('mark.webp')
        ->assertSee('ユーザーID')
        ->assertDontSee('パスワードを忘れた');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create(['login_id' => 'accounta.user']);

    $response = $this->post('/login', [
        'login_id' => 'ACCOUNTA.USER',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'login_id' => $user->login_id,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users cannot authenticate using an email address', function () {
    User::factory()->create([
        'login_id' => 'accounta.user',
        'email' => 'user@example.com',
    ]);

    $this->post('/login', [
        'login_id' => 'user@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('deferred authentication features and api routes are unavailable', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->get('/passkeys/login/options')->assertNotFound();
    $this->get('/api/user')->assertNotFound();
});
