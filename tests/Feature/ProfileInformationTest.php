<?php

use App\Models\User;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;

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
