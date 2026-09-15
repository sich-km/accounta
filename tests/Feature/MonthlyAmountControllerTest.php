<?php

use App\Models\Department;
use App\Models\ManagementAccount;
use App\Models\MonthlyAmount;
use App\Models\Organization;
use App\Models\User;

test('monthly amount index uses the shared action menu', function () {
    $user = User::factory()->create();
    MonthlyAmount::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => Department::factory()->for($user->organization),
        'management_account_id' => ManagementAccount::factory()->for($user->organization),
    ]);

    $this->actingAs($user)->get(route('amounts.index'))
        ->assertSee('aria-label="操作メニューを開く"', false)
        ->assertSee('x-teleport="body"', false)
        ->assertSeeText('編集')
        ->assertSeeText('削除');
});

test('users can create multiple monthly amount details for the same dimensions', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->get(route('amounts.create'))
        ->assertOk()
        ->assertSee('予算・実績を登録');

    foreach (['500000', '300000'] as $amount) {
        $this->actingAs($user)
            ->post(route('amounts.store'), [
                'period' => '2026-04',
                'department_id' => $department->id,
                'management_account_id' => $managementAccount->id,
                'type' => 'budget',
                'amount' => $amount,
                'memo' => '外注費',
            ])
            ->assertRedirect(route('amounts.index'));
    }

    $this->assertDatabaseCount('monthly_amounts', 2);
    $storedAmount = MonthlyAmount::query()
        ->where('amount', 500000)
        ->firstOrFail();

    expect($storedAmount->organization_id)->toBe($user->organization_id);
    expect($storedAmount->period->toDateString())->toBe('2026-04-01');
    expect($storedAmount->department_id)->toBe($department->id);
    expect($storedAmount->management_account_id)->toBe($managementAccount->id);
    expect($storedAmount->type)->toBe('budget');
    expect($storedAmount->amount)->toBe('500000.00');
    expect($storedAmount->source)->toBe('manual');
});

test('users cannot assign masters from another organization', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $otherManagementAccount = ManagementAccount::factory()->for(Organization::factory())->create();

    $this->actingAs($user)
        ->post(route('amounts.store'), [
            'period' => '2026-04',
            'department_id' => $department->id,
            'management_account_id' => $otherManagementAccount->id,
            'type' => 'actual',
            'amount' => '1000',
        ])
        ->assertSessionHasErrors('management_account_id');

    $this->assertDatabaseCount('monthly_amounts', 0);
});

test('existing amounts can retain an inactive master when updated', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create();
    $amount = MonthlyAmount::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'period' => '2026-04-01',
    ]);
    $department->update(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('amounts.edit', $amount))
        ->assertOk()
        ->assertSee('（無効）');

    $this->actingAs($user)
        ->put(route('amounts.update', $amount), [
            'period' => '2026-04',
            'department_id' => $department->id,
            'management_account_id' => $managementAccount->id,
            'type' => 'actual',
            'amount' => '-1200.50',
            'memo' => '調整',
        ])
        ->assertRedirect(route('amounts.index'));

    $this->assertDatabaseHas('monthly_amounts', [
        'id' => $amount->id,
        'department_id' => $department->id,
        'type' => 'actual',
        'amount' => '-1200.50',
        'memo' => '調整',
    ]);
});

test('monthly amounts from another organization return 404', function () {
    $user = User::factory()->create();
    $otherAmount = MonthlyAmount::factory()->create();

    $this->actingAs($user)
        ->get(route('amounts.edit', $otherAmount))
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('amounts.update', $otherAmount), [
            'period' => '2026-04',
            'department_id' => Department::factory()->for($user->organization)->create()->id,
            'management_account_id' => ManagementAccount::factory()->for($user->organization)->create()->id,
            'type' => 'actual',
            'amount' => '1000',
        ])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('amounts.destroy', $otherAmount))
        ->assertNotFound();

    $this->assertModelExists($otherAmount);
});

test('users can delete their monthly amount', function () {
    $user = User::factory()->create();
    $amount = MonthlyAmount::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => Department::factory()->for($user->organization),
        'management_account_id' => ManagementAccount::factory()->for($user->organization),
    ]);

    $this->actingAs($user)
        ->delete(route('amounts.destroy', $amount))
        ->assertRedirect(route('amounts.index'));

    $this->assertModelMissing($amount);
});
