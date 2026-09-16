<?php

use App\Models\BudgetActualAccount;
use App\Models\BudgetActualEntry;
use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;

test('budget actual entry index uses the shared action menu', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $user = User::factory()->create();
    BudgetActualEntry::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => Department::factory()->for($user->organization),
        'budget_actual_account_id' => BudgetActualAccount::factory()->for($user->organization),
        'period' => '2026-04-01',
    ]);

    $this->actingAs($user)->get(route('budget-actual-entries.index'))
        ->assertViewHas('currentFiscalYear', 2026)
        ->assertViewHas('selectedYear', 2026)
        ->assertSee('<a href="'.route('budget-actual-entries.create').'"', false)
        ->assertSee('name="type"', false)
        ->assertSee('name="year"', false)
        ->assertSee('name="month"', false)
        ->assertSee('name="department_id"', false)
        ->assertSee('name="budget_actual_account_id"', false)
        ->assertSee('name="per_page"', false)
        ->assertSeeText('区分')
        ->assertSeeText('対象年度')
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

test('budget actual entry index supports the selected page size', function (int $perPage) {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();
    BudgetActualEntry::factory()
        ->count($perPage + 1)
        ->create([
            'organization_id' => $user->organization_id,
            'department_id' => $department->id,
            'budget_actual_account_id' => $budgetActualAccount->id,
            'period' => '2026-04-01',
        ]);

    $response = $this->actingAs($user)->get(route('budget-actual-entries.index', [
        'per_page' => $perPage,
    ]));

    $response
        ->assertViewHas('budgetActualEntries', fn ($budgetActualEntries): bool => $budgetActualEntries->perPage() === $perPage
            && $budgetActualEntries->count() === $perPage
            && $budgetActualEntries->total() === $perPage + 1)
        ->assertViewHas('perPageOptions', [50, 100, 150, 200])
        ->assertSee('value="'.$perPage.'" selected', false);
})->with([
    '50件' => 50,
    '100件' => 100,
    '150件' => 150,
    '200件' => 200,
]);

test('budget actual entries can be filtered by type year and month within the organization', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();

    foreach ([
        ['period' => '2026-04-01', 'type' => 'budget', 'memo' => '2026年4月予算'],
        ['period' => '2026-04-01', 'type' => 'actual', 'memo' => '2026年4月実績'],
        ['period' => '2026-05-01', 'type' => 'actual', 'memo' => '2026年5月実績'],
        ['period' => '2027-04-01', 'type' => 'actual', 'memo' => '2027年4月実績'],
    ] as $attributes) {
        BudgetActualEntry::factory()->create([
            'organization_id' => $user->organization_id,
            'department_id' => $department->id,
            'budget_actual_account_id' => $budgetActualAccount->id,
            ...$attributes,
        ]);
    }

    BudgetActualEntry::factory()->create([
        'period' => '2026-04-01',
        'type' => 'actual',
        'memo' => '他組織の2026年4月実績',
    ]);

    $this->actingAs($user)
        ->get(route('budget-actual-entries.index', ['type' => 'actual', 'year' => 2026, 'month' => 4]))
        ->assertViewHas('selectedType', 'actual')
        ->assertViewHas('selectedYear', 2026)
        ->assertViewHas('selectedMonth', 4)
        ->assertViewHas('availableYears', [2027, 2026])
        ->assertSee('aria-label="予算・実績の該当件数：1件"', false)
        ->assertSee('href="'.route('budget-actual-entries.index', ['reset_filters' => 1]).'"', false)
        ->assertSeeText('2026年4月実績')
        ->assertDontSeeText('2026年4月予算')
        ->assertDontSeeText('2026年5月実績')
        ->assertDontSeeText('2027年4月実績')
        ->assertDontSeeText('他組織の2026年4月実績');
});

test('budget actual entries can be filtered by department and budget actual account', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $user = User::factory()->create();
    $targetDepartment = Department::factory()->for($user->organization)->create([
        'code' => 'D100',
        'name' => '対象部門',
    ]);
    $otherDepartment = Department::factory()->for($user->organization)->create([
        'code' => 'D200',
        'name' => '対象外部門',
    ]);
    $targetBudgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create([
        'code' => '4000',
        'name' => '対象科目',
    ]);
    $otherBudgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create([
        'code' => '5000',
        'name' => '対象外科目',
    ]);

    foreach ([
        [
            'department_id' => $targetDepartment->id,
            'budget_actual_account_id' => $targetBudgetActualAccount->id,
            'memo' => '部門・科目ともに一致',
        ],
        [
            'department_id' => $otherDepartment->id,
            'budget_actual_account_id' => $targetBudgetActualAccount->id,
            'memo' => '部門が不一致',
        ],
        [
            'department_id' => $targetDepartment->id,
            'budget_actual_account_id' => $otherBudgetActualAccount->id,
            'memo' => '科目が不一致',
        ],
    ] as $attributes) {
        BudgetActualEntry::factory()->create([
            'organization_id' => $user->organization_id,
            'period' => '2026-04-01',
            ...$attributes,
        ]);
    }

    $this->actingAs($user)
        ->get(route('budget-actual-entries.index', [
            'department_id' => $targetDepartment->id,
            'budget_actual_account_id' => $targetBudgetActualAccount->id,
        ]))
        ->assertViewHas('selectedDepartmentId', $targetDepartment->id)
        ->assertViewHas('selectedBudgetActualAccountId', $targetBudgetActualAccount->id)
        ->assertSeeText('部門・科目ともに一致')
        ->assertDontSeeText('部門が不一致')
        ->assertDontSeeText('科目が不一致');
});

test('budget actual entry filter preferences persist after logout and login and remain user specific', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $user = User::factory()->create(['login_id' => 'budget.actual.entry.user']);
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();
    BudgetActualEntry::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => $department->id,
        'budget_actual_account_id' => $budgetActualAccount->id,
        'period' => '2025-04-01',
        'type' => 'actual',
        'memo' => '保存条件の対象予実',
    ]);
    $otherUser = User::factory()->create(['login_id' => 'other.budget.actual.entry.user']);

    $this->actingAs($user)->get(route('budget-actual-entries.index', [
        'type' => 'actual',
        'year' => 2025,
        'month' => 4,
        'department_id' => $department->id,
        'budget_actual_account_id' => $budgetActualAccount->id,
        'per_page' => 100,
    ]));

    $this->post(route('logout'))->assertRedirect('/');
    $this->post('/login', [
        'login_id' => $user->login_id,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('budget-actual-entries.index'))
        ->assertViewHas('selectedType', 'actual')
        ->assertViewHas('selectedYear', 2025)
        ->assertViewHas('selectedMonth', 4)
        ->assertViewHas('selectedDepartmentId', $department->id)
        ->assertViewHas('selectedBudgetActualAccountId', $budgetActualAccount->id)
        ->assertViewHas('selectedPerPage', 100)
        ->assertSeeText('保存条件の対象予実');

    $this->actingAs($otherUser)->get(route('budget-actual-entries.index'))
        ->assertViewHas('selectedType', null)
        ->assertViewHas('selectedYear', 2026)
        ->assertViewHas('selectedMonth', null)
        ->assertViewHas('selectedDepartmentId', null)
        ->assertViewHas('selectedBudgetActualAccountId', null)
        ->assertViewHas('selectedPerPage', 50);
});

test('budget actual entry filters can clear remembered preferences', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();
    BudgetActualEntry::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => $department->id,
        'budget_actual_account_id' => $budgetActualAccount->id,
        'period' => '2025-04-01',
    ]);

    $this->actingAs($user)->get(route('budget-actual-entries.index', [
        'type' => 'budget',
        'year' => 2025,
        'month' => 4,
        'department_id' => $department->id,
        'budget_actual_account_id' => $budgetActualAccount->id,
        'per_page' => 100,
    ]));

    $this->get(route('budget-actual-entries.index', ['reset_filters' => 1]))
        ->assertViewHas('selectedType', null)
        ->assertViewHas('selectedYear', 2026)
        ->assertViewHas('selectedMonth', null)
        ->assertViewHas('selectedDepartmentId', null)
        ->assertViewHas('selectedBudgetActualAccountId', null)
        ->assertViewHas('selectedPerPage', 50);

    $this->get(route('budget-actual-entries.index'))
        ->assertViewHas('selectedType', null)
        ->assertViewHas('selectedYear', 2026)
        ->assertViewHas('selectedMonth', null)
        ->assertViewHas('selectedDepartmentId', null)
        ->assertViewHas('selectedBudgetActualAccountId', null)
        ->assertViewHas('selectedPerPage', 50);
});

test('budget actual entry index defaults to the current fiscal year based on the company start month', function () {
    $this->travelTo(CarbonImmutable::create(2026, 2, 15, 12, 0, 0, 'Asia/Tokyo'));
    $user = User::factory()->create();
    $user->company->update(['fiscal_year_start_month' => 4]);
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();

    foreach ([
        ['period' => '2025-03-01', 'memo' => '前年度の予実'],
        ['period' => '2025-04-01', 'memo' => '当年度開始月の予実'],
        ['period' => '2026-03-01', 'memo' => '当年度終了月の予実'],
        ['period' => '2026-04-01', 'memo' => '翌年度の予実'],
    ] as $attributes) {
        BudgetActualEntry::factory()->create([
            'organization_id' => $user->organization_id,
            'department_id' => $department->id,
            'budget_actual_account_id' => $budgetActualAccount->id,
            ...$attributes,
        ]);
    }

    $this->actingAs($user)
        ->get(route('budget-actual-entries.index'))
        ->assertViewHas('currentFiscalYear', 2025)
        ->assertViewHas('selectedYear', 2025)
        ->assertViewHas('availableYears', [2026, 2025, 2024])
        ->assertViewHas('fiscalYearMonths', [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3])
        ->assertSeeText('対象年度')
        ->assertSeeText('2025年度')
        ->assertSeeText('当年度開始月の予実')
        ->assertSeeText('当年度終了月の予実')
        ->assertDontSeeText('前年度の予実')
        ->assertDontSeeText('翌年度の予実');
});

test('selecting a fiscal year and month maps the month to the correct calendar year', function () {
    $user = User::factory()->create();
    $user->company->update(['fiscal_year_start_month' => 4]);
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();

    foreach ([
        ['period' => '2026-02-01', 'memo' => '2025年度2月の予実'],
        ['period' => '2027-02-01', 'memo' => '2026年度2月の予実'],
    ] as $attributes) {
        BudgetActualEntry::factory()->create([
            'organization_id' => $user->organization_id,
            'department_id' => $department->id,
            'budget_actual_account_id' => $budgetActualAccount->id,
            ...$attributes,
        ]);
    }

    $this->actingAs($user)
        ->get(route('budget-actual-entries.index', ['year' => 2026, 'month' => 2]))
        ->assertViewHas('selectedYear', 2026)
        ->assertViewHas('selectedMonth', 2)
        ->assertSeeText('2026年度2月の予実')
        ->assertDontSeeText('2025年度2月の予実');
});

test('budget actual entry index can show records from every fiscal year', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();

    foreach ([
        ['period' => '2025-04-01', 'memo' => '2025年度の予実'],
        ['period' => '2026-04-01', 'memo' => '2026年度の予実'],
    ] as $attributes) {
        BudgetActualEntry::factory()->create([
            'organization_id' => $user->organization_id,
            'department_id' => $department->id,
            'budget_actual_account_id' => $budgetActualAccount->id,
            ...$attributes,
        ]);
    }

    $this->actingAs($user)
        ->get(route('budget-actual-entries.index', ['year' => '']))
        ->assertViewHas('selectedYear', null)
        ->assertViewHas('selectedMonth', null)
        ->assertSeeText('2025年度の予実')
        ->assertSeeText('2026年度の予実');
});

test('invalid budget actual entry filters are ignored', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $user = User::factory()->create();
    $otherDepartment = Department::factory()->create([
        'code' => 'OTHER-D',
        'name' => '他組織部門',
    ]);
    $otherBudgetActualAccount = BudgetActualAccount::factory()->create([
        'code' => 'OTHER-A',
        'name' => '他組織科目',
    ]);
    BudgetActualEntry::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => Department::factory()->for($user->organization),
        'budget_actual_account_id' => BudgetActualAccount::factory()->for($user->organization),
        'period' => '2026-04-01',
        'memo' => '表示対象',
    ]);

    $this->actingAs($user)
        ->get(route('budget-actual-entries.index', [
            'type' => 'forecast',
            'year' => 9999,
            'month' => 13,
            'department_id' => $otherDepartment->id,
            'budget_actual_account_id' => $otherBudgetActualAccount->id,
            'per_page' => 1000,
        ]))
        ->assertViewHas('selectedType', null)
        ->assertViewHas('selectedYear', 2026)
        ->assertViewHas('selectedMonth', null)
        ->assertViewHas('selectedDepartmentId', null)
        ->assertViewHas('selectedBudgetActualAccountId', null)
        ->assertViewHas('selectedPerPage', 50)
        ->assertSeeText('表示対象')
        ->assertDontSeeText('他組織部門')
        ->assertDontSeeText('他組織科目');
});

test('users can create multiple budget actual entry details for the same dimensions', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();

    $this->actingAs($user)
        ->get(route('budget-actual-entries.create'))
        ->assertOk()
        ->assertSee('予算・実績を登録');

    foreach (['500000', '300000'] as $amount) {
        $this->actingAs($user)
            ->post(route('budget-actual-entries.store'), [
                'period' => '2026-04',
                'department_id' => $department->id,
                'budget_actual_account_id' => $budgetActualAccount->id,
                'type' => 'budget',
                'amount' => $amount,
                'memo' => '外注費',
            ])
            ->assertRedirect(route('budget-actual-entries.index'));
    }

    $this->assertDatabaseCount('budget_actual_entries', 2);
    $storedBudgetActualEntry = BudgetActualEntry::query()
        ->where('amount', 500000)
        ->firstOrFail();

    expect($storedBudgetActualEntry->organization_id)->toBe($user->organization_id);
    expect($storedBudgetActualEntry->period->toDateString())->toBe('2026-04-01');
    expect($storedBudgetActualEntry->department_id)->toBe($department->id);
    expect($storedBudgetActualEntry->budget_actual_account_id)->toBe($budgetActualAccount->id);
    expect($storedBudgetActualEntry->type)->toBe('budget');
    expect($storedBudgetActualEntry->amount)->toBe('500000.00');
    expect($storedBudgetActualEntry->source)->toBe('manual');
});

test('users cannot assign masters from another organization', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $otherBudgetActualAccount = BudgetActualAccount::factory()->for(Organization::factory())->create();

    $this->actingAs($user)
        ->post(route('budget-actual-entries.store'), [
            'period' => '2026-04',
            'department_id' => $department->id,
            'budget_actual_account_id' => $otherBudgetActualAccount->id,
            'type' => 'actual',
            'amount' => '1000',
        ])
        ->assertSessionHasErrors('budget_actual_account_id');

    $this->assertDatabaseCount('budget_actual_entries', 0);
});

test('existing budget actual entries can retain an inactive master when updated', function () {
    $user = User::factory()->create();
    $department = Department::factory()->for($user->organization)->create();
    $budgetActualAccount = BudgetActualAccount::factory()->for($user->organization)->create();
    $budgetActualEntry = BudgetActualEntry::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => $department->id,
        'budget_actual_account_id' => $budgetActualAccount->id,
        'period' => '2026-04-01',
    ]);
    $department->update(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('budget-actual-entries.edit', $budgetActualEntry))
        ->assertOk()
        ->assertSee('（無効）');

    $this->actingAs($user)
        ->put(route('budget-actual-entries.update', $budgetActualEntry), [
            'period' => '2026-04',
            'department_id' => $department->id,
            'budget_actual_account_id' => $budgetActualAccount->id,
            'type' => 'actual',
            'amount' => '-1200.50',
            'memo' => '調整',
        ])
        ->assertRedirect(route('budget-actual-entries.index'));

    $this->assertDatabaseHas('budget_actual_entries', [
        'id' => $budgetActualEntry->id,
        'department_id' => $department->id,
        'type' => 'actual',
        'amount' => '-1200.50',
        'memo' => '調整',
    ]);
});

test('budget actual entries from another organization return 404', function () {
    $user = User::factory()->create();
    $otherBudgetActualEntry = BudgetActualEntry::factory()->create();

    $this->actingAs($user)
        ->get(route('budget-actual-entries.edit', $otherBudgetActualEntry))
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('budget-actual-entries.update', $otherBudgetActualEntry), [
            'period' => '2026-04',
            'department_id' => Department::factory()->for($user->organization)->create()->id,
            'budget_actual_account_id' => BudgetActualAccount::factory()->for($user->organization)->create()->id,
            'type' => 'actual',
            'amount' => '1000',
        ])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('budget-actual-entries.destroy', $otherBudgetActualEntry))
        ->assertNotFound();

    $this->assertModelExists($otherBudgetActualEntry);
});

test('users can delete their budget actual entry', function () {
    $user = User::factory()->create();
    $budgetActualEntry = BudgetActualEntry::factory()->create([
        'organization_id' => $user->organization_id,
        'department_id' => Department::factory()->for($user->organization),
        'budget_actual_account_id' => BudgetActualAccount::factory()->for($user->organization),
    ]);

    $this->actingAs($user)
        ->delete(route('budget-actual-entries.destroy', $budgetActualEntry))
        ->assertRedirect(route('budget-actual-entries.index'));

    $this->assertModelMissing($budgetActualEntry);
});
