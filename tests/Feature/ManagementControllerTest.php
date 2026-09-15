<?php

use App\Models\User;

test('company administrators can open the management page without company management', function () {
    $user = User::factory()->companyAdmin()->create();

    $this->actingAs($user)
        ->get(route('management.index'))
        ->assertOk()
        ->assertSeeText('部門マスタ')
        ->assertSeeText('予実管理科目マスタ')
        ->assertSeeText('仕訳用勘定科目マスタ')
        ->assertDontSee('href="'.route('companies.index').'"', false);
});

test('system administrators can open company management from the management page', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('management.index'))
        ->assertOk()
        ->assertSeeText('会社情報管理')
        ->assertSee('href="'.route('companies.index').'"', false);
});

test('general users cannot open the management page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('management.index'))
        ->assertForbidden();
});

test('guests are redirected from the management page to login', function () {
    $this->get(route('management.index'))
        ->assertRedirect(route('login'));
});
