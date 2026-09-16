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
        ->assertSee('<a href="'.route('amounts.create').'"', false)
        ->assertSee('name="type"', false)
        ->assertSee('name="year"', false)
        ->assertSee('name="month"', false)
        ->assertSee('name="department_id"', false)
        ->assertSee('name="management_account_id"', false)
        ->assertSee('name="per_page"', false)
        ->assertSeeText('区分')
        ->assertSeeText('対象年')
        ->assertSeeText('対象月')
        ->assertSeeText('部門')
        ->assertSeeText('予実管理科目')
        ->assertSeeText('表示件数')
        ->assertSee('class="w-full min-w-[64rem] divide-y divide-gray-200"', false)
        ->assertSee('class="w-20 whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800"', false)
        ->assertSee('class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800"', false)
        ->assertSee('class="max-w-xs truncate px-4 py-4 text-base text-gray-900"', false)
        ->assertSee('class="whitespace-nowrap px-4 py-4 text-right text-base font-semibold text-gray-900"', false)
        ->assertSee('aria-label="操作メニューを開く"', false)
        ->assertSee('x-teleport="body"', false)
        ->assertSeeText('編集')
        ->assertSeeText('削除');
});

test('monthly amount index supports the selected page size', function (int $perPage) {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create();
    MonthlyAmount::factory()
        ->count($perPage + 1)
        ->create([
            'organization_id' => $user->organization_id,
            'department_id' => $department->id,
            'management_account_id' => $managementAccount->id,
        ]);

    $response = $this->actingAs($user)->get(route('amounts.index', [
        'per_page' => $perPage,
    ]));

    $response
        ->assertViewHas('amounts', fn ($amounts): bool => $amounts->perPage() === $perPage
            && $amounts->count() === $perPage
            && $amounts->total() === $perPage + 1)
        ->assertViewHas('perPageOptions', [50, 100, 150, 200])
        ->assertSee('value="'.$perPage.'" selected', false);
})->with([
    '50件' => 50,
    '100件' => 100,
    '150件' => 150,
    '200件' => 200,
]);

test('monthly amounts can be filtered by type year and month within the organization', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create();

    foreach ([
        ['period' => '2026-04-01', 'type' => 'budget', 'memo' => '2026年4月予算'],
        ['period' => '2026-04-01', 'type' => 'actual', 'memo' => '2026年4月実績'],
        ['period' => '2026-05-01', 'type' => 'actual', 'memo' => '2026年5月実績'],
        ['period' => '2027-04-01', 'type' => 'actual', 'memo' => '2027年4月実績'],
    ] as $attributes) {
        MonthlyAmount::factory()->create([
            'organization_id' => $user->organization_id,
            'department_id' => $department->id,
            'management_account_id' => $managementAccount->id,
            ...$attributes,
        ]);
    }

    MonthlyAmount::factory()->create([
        'period' => '2026-04-01',
        'type' => 'actual',
        'memo' => '他組織の2026年4月実績',
    ]);

    $this->actingAs($user)
        ->get(route('amounts.index', ['type' => 'actual', 'year' => 2026, 'month' => 4]))
        ->assertViewHas('selectedType', 'actual')
        ->assertViewHas('selectedYear', 2026)
        ->assertViewHas('selectedMonth', 4)
        ->assertViewHas('availableYears', [2027, 2026])
        ->assertSeeText('2026年4月実績')
        ->assertDontSeeText('2026年4月予算')
        ->assertDontSeeText('2026年5月実績')
        ->assertDontSeeText('2027年4月実績')
        ->assertDontSeeText('他組織の2026年4月実績');
});

test('monthly amounts can be filtered by department and management account', function () {
    $user = User::factory()->create();
    $targetDepartment = Department::factory()->for($user->organization)->create([
        'code' => 'D100',
        'name' => '対象部門',
    ]);
    $otherDepartment = Department::factory()->for($user->organization)->create([
        'code' => 'D200',
        'name' => '対象外部門',
    ]);
    $targetManagementAccount = ManagementAccount::factory()->for($user->organization)->create([
        'code' => '4000',
        'name' => '対象科目',
    ]);
    $otherManagementAccount = ManagementAccount::factory()->for($user->organization)->create([
        'code' => '5000',
        'name' => '対象外科目',
    ]);

    foreach ([
        [
            'department_id' => $targetDepartment->id,
            'management_account_id' => $targetManagementAccount->id,
            'memo' => '部門・科目ともに一致',
        ],
        [
            'department_id' => $otherDepartment->id,
            'management_account_id' => $targetManagementAccount->id,
            'memo' => '部門が不一致',
        ],
        [
            'department_id' => $targetDepartment->id,
            'management_account_id' => $otherManagementAccount->id,
            'memo' => '科目が不一致',
        ],
    ] as $attributes) {
        MonthlyAmount::factory()->create([
            'organization_id' => $user->organization_id,
            ...$attributes,
        ]);
    }

    $this->actingAs($user)
        ->get(route('amounts.index', [
            'department_id' => $targetDepartment->id,
            'management_account_id' => $targetManagementAccount->id,
        ]))
        ->assertViewHas('selectedDepartmentId', $targetDepartment->id)
        ->assertViewHas('selectedManagementAccountId', $targetManagementAccount->id)
        ->assertSeeText('部門・科目ともに一致')
        ->assertDontSeeText('部門が不一致')
        ->assertDontSeeText('科目が不一致');
});

test('selecting only a year shows all monthly amounts in that year', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $managementAccount = ManagementAccount::factory()->for($user->organization)->create();

    foreach ([
        ['period' => '2026-04-01', 'memo' => '2026年4月'],
        ['period' => '2026-12-01', 'memo' => '2026年12月'],
        ['period' => '2027-01-01', 'memo' => '2027年1月'],
    ] as $attributes) {
        MonthlyAmount::factory()->create([
            'organization_id' => $user->organization_id,
            'department_id' => $department->id,
            'management_account_id' => $managementAccount->id,
            ...$attributes,
        ]);
    }

    $this->actingAs($user)
        ->get(route('amounts.index', ['year' => 2026]))
        ->assertSeeText('2026年4月')
        ->assertSeeText('2026年12月')
        ->assertDontSeeText('2027年1月');
});

test('invalid monthly amount filters are ignored', function () {
    $user = User::factory()->create();
    $otherDepartment = Department::factory()->create([
        'code' => 'OTHER-D',
        'name' => '他組織部門',
    ]);
    $otherManagementAccount = ManagementAccount::factory()->create([
        'code' => 'OTHER-A',
        'name' => '他組織科目',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => Department::factory()->for($user->organization),
        'management_account_id' => ManagementAccount::factory()->for($user->organization),
        'period' => '2026-04-01',
        'memo' => '表示対象',
    ]);

    $this->actingAs($user)
        ->get(route('amounts.index', [
            'type' => 'forecast',
            'year' => 9999,
            'month' => 13,
            'department_id' => $otherDepartment->id,
            'management_account_id' => $otherManagementAccount->id,
            'per_page' => 1000,
        ]))
        ->assertViewHas('selectedType', null)
        ->assertViewHas('selectedYear', null)
        ->assertViewHas('selectedMonth', null)
        ->assertViewHas('selectedDepartmentId', null)
        ->assertViewHas('selectedManagementAccountId', null)
        ->assertViewHas('selectedPerPage', 50)
        ->assertSeeText('表示対象')
        ->assertDontSeeText('他組織部門')
        ->assertDontSeeText('他組織科目');
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
