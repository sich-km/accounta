<?php

use App\Enums\UserType;
use App\Livewire\Profile\UserDepartmentForm;
use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;
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
    $department = Department::factory()->for($user->organization)->create([
        'code' => 'D120',
        'name' => '人事部',
    ]);
    $user->update(['department_id' => $department->id]);
    Organization::factory()->create(['name' => '他社組織']);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSeeText('組織情報')
        ->assertSeeText('テスト会社')
        ->assertSeeText('test-company')
        ->assertSeeText('東京本部')
        ->assertSeeText('D120 人事部')
        ->assertSeeText('一般ユーザー')
        ->assertSeeText('4月')
        ->assertDontSeeText('他社組織');
});

test('users can update their department from the organization information section', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $this->actingAs($user);

    Livewire::test(UserDepartmentForm::class)
        ->set('departmentId', (string) $department->id)
        ->call('updateDepartment')
        ->assertHasNoErrors();

    expect($user->refresh()->department_id)->toBe($department->id);
});

test('users cannot select a department from another organization', function () {
    $user = User::factory()->create();
    $otherDepartment = Department::factory()->create();
    $this->actingAs($user);

    Livewire::test(UserDepartmentForm::class)
        ->set('departmentId', (string) $otherDepartment->id)
        ->call('updateDepartment')
        ->assertHasErrors('departmentId');

    expect($user->refresh()->department_id)->toBeNull();
});

test('database rejects a user department from another organization', function () {
    $user = User::factory()->create();
    $otherDepartment = Department::factory()->create();

    expect(fn () => $user->update(['department_id' => $otherDepartment->id]))
        ->toThrow(QueryException::class);
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
