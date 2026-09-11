<?php

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;

test('profile screen displays the current organization information', function () {
    $user = User::factory()->create(['user_type' => UserType::User]);
    $user->company->update([
        'code' => 'test-company',
        'name' => 'テスト会社',
        'fiscal_year_start_month' => 4,
    ]);
    $user->organization->update(['name' => '東京本部']);
    Organization::factory()->create(['name' => '他社組織']);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSeeText('組織情報')
        ->assertSeeText('テスト会社')
        ->assertSeeText('test-company')
        ->assertSeeText('東京本部')
        ->assertSeeText('一般ユーザー')
        ->assertSeeText('4月')
        ->assertDontSeeText('他社組織');
});

test('current profile information is available', function () {
    $this->actingAs($user = User::factory()->create());

    $component = Livewire::test(UpdateProfileInformationForm::class);

    expect($component->state['name'])->toEqual($user->name);
});

test('only the profile display name can be updated', function () {
    $this->actingAs($user = User::factory()->create([
        'login_id' => 'original.user',
        'email' => null,
    ]));

    Livewire::test(UpdateProfileInformationForm::class)
        ->set('state', [
            'name' => 'Test Name',
            'login_id' => 'changed.user',
            'email' => 'test@example.com',
        ])
        ->call('updateProfileInformation');

    $user->refresh();

    expect($user->name)->toEqual('Test Name');
    expect($user->login_id)->toEqual('original.user');
    expect($user->email)->toBeNull();
});
