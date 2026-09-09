<?php

use App\Models\Company;
use App\Models\User;

test('admins can create update and delete empty companies', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);

    $this->actingAs($admin)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSeeText($admin->company->name);

    $this->actingAs($admin)
        ->post(route('companies.store'), [
            'code' => ' ACME ',
            'name' => ' Acme株式会社 ',
            'fiscal_year_start_month' => 4,
        ])
        ->assertRedirect(route('companies.index'));

    $company = Company::query()->where('code', 'acme')->firstOrFail();

    expect($company->name)->toBe('Acme株式会社');
    expect($company->fiscal_year_start_month)->toBe(4);

    $this->actingAs($admin)
        ->put(route('companies.update', $company), [
            'code' => 'acme-new',
            'name' => 'Acme合同会社',
            'fiscal_year_start_month' => 7,
        ])
        ->assertRedirect(route('companies.index'));

    $company->refresh();

    expect($company->code)->toBe('acme-new');
    expect($company->name)->toBe('Acme合同会社');
    expect($company->fiscal_year_start_month)->toBe(7);

    $this->actingAs($admin)
        ->delete(route('companies.destroy', $company))
        ->assertRedirect(route('companies.index'));

    $this->assertModelMissing($company);
});

test('users cannot manage companies', function () {
    $user = User::factory()->create(['user_type' => 'user']);

    $this->actingAs($user)
        ->get(route('companies.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('companies.store'), [
            'code' => 'acme',
            'name' => 'Acme株式会社',
            'fiscal_year_start_month' => 4,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('companies', ['code' => 'acme']);
});

test('admins cannot delete their own company', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);

    $this->actingAs($admin)
        ->delete(route('companies.destroy', $admin->company))
        ->assertForbidden();

    $this->assertModelExists($admin->company);
});

test('company input is validated', function () {
    $admin = User::factory()->create(['user_type' => 'admin']);

    $this->actingAs($admin)
        ->post(route('companies.store'), [
            'code' => 'invalid code',
            'name' => '',
            'fiscal_year_start_month' => 13,
        ])
        ->assertSessionHasErrors([
            'code',
            'name',
            'fiscal_year_start_month',
        ]);

    $this->assertDatabaseMissing('companies', ['code' => 'invalid code']);
});
