<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\ManagementAccount;
use App\Models\MonthlyAmount;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;

test('dashboard displays statistics for the current organization', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $company = Company::factory()->create(['fiscal_year_start_month' => 4]);
    $organization = Organization::factory()->for($company)->create(['name' => 'テスト株式会社']);
    $user = User::factory()->for($organization)->create(['name' => '非表示ユーザー']);
    $department = Department::factory()->for($organization)->create();
    Department::factory()->for($organization)->inactive()->create();
    $managementAccount = ManagementAccount::factory()->for($organization)->create();
    ManagementAccount::factory()->for($organization)->inactive()->create();
    LedgerAccount::factory()->for($organization)->create();
    LedgerAccount::factory()->for($organization)->inactive()->create();
    JournalEntry::factory()->for($organization)->create(['entry_date' => '2026-04-01']);
    JournalEntry::factory()->for($organization)->create(['entry_date' => '2027-03-31']);
    JournalEntry::factory()->for($organization)->create(['entry_date' => '2026-03-31']);
    JournalEntry::factory()->create(['entry_date' => '2026-04-01']);

    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'type' => 'budget',
        'period' => '2026-04-01',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'type' => 'budget',
        'period' => '2027-03-01',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'type' => 'actual',
        'period' => '2026-06-01',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'type' => 'budget',
        'period' => '2026-03-01',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'type' => 'budget',
        'period' => '2027-04-01',
    ]);
    MonthlyAmount::factory()->create(['period' => '2026-04-01']);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'asset_category' => 'machinery_equipment',
        'depreciation_method' => 'straight_line',
        'acquisition_cost' => '120000.00',
        'accumulated_depreciation' => '22000.00',
        'status' => 'held',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'asset_category' => 'furniture_fixture',
        'depreciation_method' => 'straight_line',
        'acquisition_cost' => '80000.00',
        'accumulated_depreciation' => '30000.00',
        'status' => 'held',
    ]);
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'acquisition_cost' => '1000000.00',
        'accumulated_depreciation' => '100000.00',
        'status' => 'sold',
    ]);
    FixedAsset::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('organization', function (Organization $dashboardOrganization) use ($organization): bool {
            return $dashboardOrganization->is($organization)
                && $dashboardOrganization->current_fiscal_year_budget_records_count === 2
                && $dashboardOrganization->current_fiscal_year_actual_records_count === 1
                && $dashboardOrganization->active_departments_count === 1
                && $dashboardOrganization->active_management_accounts_count === 1
                && $dashboardOrganization->active_ledger_accounts_count === 1
                && $dashboardOrganization->current_fiscal_year_journal_entries_count === 2;
        })
        ->assertViewHas('fixedAssetSummary', [
            'count' => 2,
            'acquisitionCost' => '200000.00',
            'bookValue' => '148000.00',
        ])
        ->assertViewHas('profitAndLossChart', [
            'revenue' => '0.00',
            'expenses' => '0.00',
            'profitOrLoss' => '0.00',
            'revenueHeightPercentage' => '0.00',
            'expenseHeightPercentage' => '0.00',
        ])
        ->assertSeeText('テスト株式会社')
        ->assertSeeInOrder(['仕訳', 'P/Lサマリー', 'B/Sサマリー', '固定資産', '予実管理', '管理', '会計学習', 'メニュー'])
        ->assertSeeText('現在年度の予算レコード')
        ->assertSeeText('現在年度の実績レコード')
        ->assertSeeText('収益・費用比較')
        ->assertSeeText('差額（税引前損益）')
        ->assertSeeText('今月予算は未登録です。')
        ->assertSeeText('今月実績は未登録です。')
        ->assertSeeText('予算と実績が揃うと表示します。')
        ->assertSeeText('固定資産管理台帳')
        ->assertSeeText('仕訳帳')
        ->assertSeeText('保有中固定資産')
        ->assertSeeText('200,000.00')
        ->assertSeeText('148,000.00')
        ->assertSeeText('表示できるデータはまだありません')
        ->assertSeeText('学習データはまだありません')
        ->assertDontSee('href="'.route('departments.index').'"', false)
        ->assertDontSee('href="'.route('management-accounts.index').'"', false)
        ->assertDontSee('href="'.route('ledger-accounts.index').'"', false)
        ->assertDontSeeText('管理メニュー')
        ->assertDontSeeText('ログインユーザー');
});

test('dashboard displays the current fiscal year profit and loss summary from journal entries', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $company = Company::factory()->create(['fiscal_year_start_month' => 4]);
    $organization = Organization::factory()->for($company)->create();
    $user = User::factory()->for($organization)->create();
    $cash = LedgerAccount::factory()->for($organization)->create([
        'code' => '1000',
        'name' => '現金及び預金',
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);
    $operatingRevenue = LedgerAccount::factory()->for($organization)->create([
        'code' => '4000',
        'name' => '売上',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    $operatingExpense = LedgerAccount::factory()->for($organization)->create([
        'code' => '6000',
        'name' => '給与手当',
        'account_type' => 'expense',
        'normal_balance' => 'debit',
    ]);
    $nonOperatingRevenue = LedgerAccount::factory()->for($organization)->create([
        'code' => '4200',
        'name' => '受取利息',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    $nonOperatingExpense = LedgerAccount::factory()->for($organization)->create([
        'code' => '7400',
        'name' => '支払利息',
        'account_type' => 'expense',
        'normal_balance' => 'debit',
    ]);
    $extraordinaryIncome = LedgerAccount::factory()->for($organization)->create([
        'code' => '4300',
        'name' => '固定資産売却益',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    $extraordinaryLoss = LedgerAccount::factory()->for($organization)->create([
        'code' => '7500',
        'name' => '投資有価証券評価損',
        'account_type' => 'expense',
        'normal_balance' => 'debit',
    ]);
    $incomeTaxes = LedgerAccount::factory()->for($organization)->create([
        'code' => '7600',
        'name' => '法人税、住民税及び事業税',
        'account_type' => 'expense',
        'normal_balance' => 'debit',
    ]);
    $unclassifiedRevenue = LedgerAccount::factory()->for($organization)->create([
        'code' => 'OTHER',
        'name' => '未分類収益',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    $journalEntry = JournalEntry::factory()->for($organization)->create([
        'entry_date' => '2026-06-30',
    ]);
    $journalEntry->lines()->createMany([
        ['organization_id' => $organization->id, 'line_number' => 1, 'ledger_account_id' => $cash->id, 'side' => 'debit', 'amount' => '680.00'],
        ['organization_id' => $organization->id, 'line_number' => 2, 'ledger_account_id' => $operatingRevenue->id, 'side' => 'credit', 'amount' => '1000.00'],
        ['organization_id' => $organization->id, 'line_number' => 3, 'ledger_account_id' => $operatingRevenue->id, 'side' => 'debit', 'amount' => '100.00'],
        ['organization_id' => $organization->id, 'line_number' => 4, 'ledger_account_id' => $operatingExpense->id, 'side' => 'debit', 'amount' => '400.00'],
        ['organization_id' => $organization->id, 'line_number' => 5, 'ledger_account_id' => $operatingExpense->id, 'side' => 'credit', 'amount' => '40.00'],
        ['organization_id' => $organization->id, 'line_number' => 6, 'ledger_account_id' => $nonOperatingRevenue->id, 'side' => 'credit', 'amount' => '100.00'],
        ['organization_id' => $organization->id, 'line_number' => 7, 'ledger_account_id' => $nonOperatingExpense->id, 'side' => 'debit', 'amount' => '20.00'],
        ['organization_id' => $organization->id, 'line_number' => 8, 'ledger_account_id' => $extraordinaryIncome->id, 'side' => 'credit', 'amount' => '50.00'],
        ['organization_id' => $organization->id, 'line_number' => 9, 'ledger_account_id' => $extraordinaryLoss->id, 'side' => 'debit', 'amount' => '10.00'],
        ['organization_id' => $organization->id, 'line_number' => 10, 'ledger_account_id' => $incomeTaxes->id, 'side' => 'debit', 'amount' => '30.00'],
        ['organization_id' => $organization->id, 'line_number' => 11, 'ledger_account_id' => $unclassifiedRevenue->id, 'side' => 'credit', 'amount' => '50.00'],
    ]);
    $previousFiscalYearEntry = JournalEntry::factory()->for($organization)->create([
        'entry_date' => '2026-03-31',
    ]);
    $previousFiscalYearEntry->lines()->create([
        'organization_id' => $organization->id,
        'line_number' => 1,
        'ledger_account_id' => $operatingRevenue->id,
        'side' => 'credit',
        'amount' => '9999.00',
    ]);
    $otherOrganization = Organization::factory()->create();
    $otherRevenue = LedgerAccount::factory()->for($otherOrganization)->create([
        'code' => '4000',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    $otherEntry = JournalEntry::factory()->for($otherOrganization)->create([
        'entry_date' => '2026-06-30',
    ]);
    $otherEntry->lines()->create([
        'organization_id' => $otherOrganization->id,
        'line_number' => 1,
        'ledger_account_id' => $otherRevenue->id,
        'side' => 'credit',
        'amount' => '9999.00',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('profitAndLossSummary', [
            'fiscalYear' => 2026,
            'periodStart' => '2026-04-01',
            'periodEnd' => '2027-03-31',
            'operatingRevenue' => '900.00',
            'operatingExpenses' => '360.00',
            'operatingProfit' => '540.00',
            'nonOperatingRevenue' => '100.00',
            'nonOperatingExpenses' => '20.00',
            'ordinaryProfit' => '620.00',
            'extraordinaryIncome' => '50.00',
            'extraordinaryLoss' => '10.00',
            'profitBeforeTax' => '660.00',
            'totalRevenue' => '1050.00',
            'expensesBeforeTax' => '390.00',
            'incomeTaxes' => '30.00',
            'unclassifiedAccountsCount' => 1,
        ])
        ->assertViewHas('profitAndLossChart', [
            'revenue' => '1050.00',
            'expenses' => '390.00',
            'profitOrLoss' => '660.00',
            'revenueHeightPercentage' => '100.00',
            'expenseHeightPercentage' => '37.14',
        ])
        ->assertSee('id="journal-profit-chart"', false)
        ->assertSee('data-chart-segment="revenue"', false)
        ->assertSee('data-chart-segment="expenses"', false)
        ->assertSee('data-chart-segment="profit"', false)
        ->assertSee('bg-sky-200', false)
        ->assertSee('bg-red-200', false)
        ->assertSee('bg-amber-100', false)
        ->assertSee('style="height: 100.00%"', false)
        ->assertSee('style="height: 37.14%"', false)
        ->assertSeeText('収益・費用比較')
        ->assertSeeText('差額（税引前損益）')
        ->assertSeeText('P/Lサマリー')
        ->assertSeeText('2026年度')
        ->assertSeeText('営業利益')
        ->assertSeeText('540.00')
        ->assertSeeText('経常利益')
        ->assertSeeText('620.00')
        ->assertSeeText('税引前当期純利益')
        ->assertSeeText('660.00')
        ->assertSeeText('損益区分を判定できない勘定科目が1件あります。');
});

test('dashboard places a loss above revenue when expenses exceed revenue', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $cash = LedgerAccount::factory()->for($organization)->create([
        'code' => '1000',
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);
    $revenue = LedgerAccount::factory()->for($organization)->create([
        'code' => '4000',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    $expense = LedgerAccount::factory()->for($organization)->create([
        'code' => '6000',
        'account_type' => 'expense',
        'normal_balance' => 'debit',
    ]);
    $journalEntry = JournalEntry::factory()->for($organization)->create([
        'entry_date' => '2026-09-16',
    ]);
    $journalEntry->lines()->createMany([
        ['organization_id' => $organization->id, 'line_number' => 1, 'ledger_account_id' => $expense->id, 'side' => 'debit', 'amount' => '600.00'],
        ['organization_id' => $organization->id, 'line_number' => 2, 'ledger_account_id' => $revenue->id, 'side' => 'credit', 'amount' => '400.00'],
        ['organization_id' => $organization->id, 'line_number' => 3, 'ledger_account_id' => $cash->id, 'side' => 'credit', 'amount' => '200.00'],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('profitAndLossChart', [
            'revenue' => '400.00',
            'expenses' => '600.00',
            'profitOrLoss' => '-200.00',
            'revenueHeightPercentage' => '66.67',
            'expenseHeightPercentage' => '100.00',
        ])
        ->assertSee('data-chart-segment="loss"', false)
        ->assertDontSee('data-chart-segment="profit"', false)
        ->assertSee('style="height: calc(100.00% - 66.67%)"', false)
        ->assertSeeText('費用＋損失');
});

test('dashboard compares the current month budget and actual profit or loss', function () {
    $this->travelTo(CarbonImmutable::create(2026, 9, 16, 12, 0, 0, 'Asia/Tokyo'));
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $department = Department::factory()->for($organization)->create();
    $revenueAccount = ManagementAccount::factory()->for($organization)->create([
        'code' => '4000',
        'name' => '売上高',
        'account_type' => 'revenue',
    ]);
    $expenseAccount = ManagementAccount::factory()->for($organization)->create([
        'code' => '6000',
        'name' => '販売費及び一般管理費',
        'account_type' => 'expense',
    ]);

    foreach ([
        ['type' => 'budget', 'management_account_id' => $revenueAccount->id, 'amount' => '1000.00'],
        ['type' => 'budget', 'management_account_id' => $expenseAccount->id, 'amount' => '600.00'],
        ['type' => 'actual', 'management_account_id' => $revenueAccount->id, 'amount' => '900.00'],
        ['type' => 'actual', 'management_account_id' => $expenseAccount->id, 'amount' => '650.00'],
    ] as $attributes) {
        MonthlyAmount::factory()->create([
            'organization_id' => $organization->id,
            'department_id' => $department->id,
            'period' => '2026-09-01',
            ...$attributes,
        ]);
    }

    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $revenueAccount->id,
        'period' => '2026-08-01',
        'type' => 'actual',
        'amount' => '9999.00',
    ]);
    MonthlyAmount::factory()->create([
        'period' => '2026-09-01',
        'type' => 'actual',
        'amount' => '9999.00',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('monthlyBudgetActualSummary', [
            'monthLabel' => '2026年9月',
            'budget' => [
                'records' => 2,
                'revenue' => '1000.00',
                'expenses' => '600.00',
                'profitOrLoss' => '400.00',
                'heightPercentage' => '100.00',
            ],
            'actual' => [
                'records' => 2,
                'revenue' => '900.00',
                'expenses' => '650.00',
                'profitOrLoss' => '250.00',
                'heightPercentage' => '62.50',
            ],
            'variance' => '-150.00',
        ])
        ->assertSee('id="monthly-budget-actual-chart"', false)
        ->assertSee('style="height: 100.00%"', false)
        ->assertSee('style="height: 62.50%"', false)
        ->assertSeeText('2026年9月の予実損益')
        ->assertSeeText('予算差異（実績損益−予算損益）')
        ->assertSeeText('-150.00円');
});

test('the dashboard renders the management section and link for administrators', function (UserType $userType) {
    $user = User::factory()->create(['user_type' => $userType]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="management-summary"', false)
        ->assertSee('href="'.route('management.index').'"', false)
        ->assertDontSee('href="'.route('departments.index').'"', false)
        ->assertDontSee('href="'.route('management-accounts.index').'"', false)
        ->assertDontSee('href="'.route('ledger-accounts.index').'"', false);
})->with([
    'system administrator' => UserType::Admin,
    'company administrator' => UserType::CompanyAdmin,
]);

test('company administrators see the ordered global navigation menu', function () {
    $user = User::factory()->companyAdmin()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSeeInOrder(['ダッシュボード', '仕訳', '固定資産', '予実管理', '管理'])
        ->assertSee('href="'.route('dashboard').'"', false)
        ->assertSee('href="'.route('management.index').'"', false)
        ->assertDontSee('href="'.route('departments.index').'"', false)
        ->assertDontSee('href="'.route('management-accounts.index').'"', false)
        ->assertDontSee('href="'.route('ledger-accounts.index').'"', false)
        ->assertDontSee('href="'.route('companies.index').'"', false);
});

test('system administrators see the management page in global navigation', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('href="'.route('management.index').'"', false)
        ->assertDontSee('href="'.route('companies.index').'"', false);
});

test('general users do not see the global navigation management menu', function () {
    $user = User::factory()->create(['user_type' => UserType::User]);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertDontSee('href="'.route('management.index').'"', false)
        ->assertDontSee('href="'.route('departments.index').'"', false)
        ->assertDontSee('href="'.route('management-accounts.index').'"', false)
        ->assertDontSee('href="'.route('ledger-accounts.index').'"', false)
        ->assertDontSee('href="'.route('companies.index').'"', false);
});

test('guests are redirected from the dashboard to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
