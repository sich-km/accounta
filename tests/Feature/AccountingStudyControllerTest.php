<?php

use App\Models\User;

test('authenticated users can open the accounting study summary', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('accounting-study.index'))
        ->assertOk()
        ->assertSeeText('会計学習')
        ->assertSeeText('学習サマリー')
        ->assertSeeText('登録問題')
        ->assertSeeText('今日の復習対象')
        ->assertSeeText('学習済み')
        ->assertSeeText('学習データはまだありません')
        ->assertSee('href="'.route('accounting-study.index').'"', false);
});

test('guests are redirected from accounting study to login', function () {
    $this->get(route('accounting-study.index'))
        ->assertRedirect(route('login'));
});
