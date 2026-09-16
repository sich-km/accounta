<?php

use App\Enums\LedgerAccountType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\MonthlyAmount;
use App\Models\User;
use Database\Seeders\InitialTenantSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

test('database seeder creates the initial tenant admin and default masters once', function () {
    $this->seed();

    $company = Company::query()
        ->where('code', Company::DEFAULT_CODE)
        ->firstOrFail();
    $organization = $company->organizations()->firstOrFail();
    $admin = User::query()->where('login_id', 'admin')->firstOrFail();
    $companyAdmin = User::query()->where('login_id', 'company_admin')->firstOrFail();
    $user = User::query()->where('login_id', 'user1')->firstOrFail();

    expect($company->name)->toBe('KM');
    expect($company->fiscal_year_start_month)->toBe(1);
    expect($organization->name)->toBe('KM');
    expect($admin->company_id)->toBe($company->id);
    expect($admin->organization_id)->toBe($organization->id);
    expect($admin->user_type)->toBe(UserType::Admin);
    expect($admin->name)->toBe('admin');
    expect(Hash::check('password', $admin->password))->toBeFalse();
    expect($companyAdmin->company_id)->toBe($company->id);
    expect($companyAdmin->organization_id)->toBe($organization->id);
    expect($companyAdmin->user_type)->toBe(UserType::CompanyAdmin);
    expect($companyAdmin->name)->toBe('company_admin');
    expect(Hash::check('password', $companyAdmin->password))->toBeFalse();
    expect($user->company_id)->toBe($company->id);
    expect($user->organization_id)->toBe($organization->id);
    expect($user->user_type)->toBe(UserType::User);
    expect($user->name)->toBe('user1');
    expect(Hash::check('password', $user->password))->toBeFalse();
    $this->assertDatabaseHas('departments', [
        'organization_id' => $organization->id,
        'code' => 'D100',
        'name' => '経営企画部',
    ]);
    $this->assertDatabaseHas('management_accounts', [
        'organization_id' => $organization->id,
        'code' => '1000',
        'name' => '現金及び預金',
    ]);
    $this->assertDatabaseHas('management_accounts', [
        'organization_id' => $organization->id,
        'code' => '6060',
        'name' => '役員報酬',
        'account_type' => 'expense',
    ]);
    $this->assertDatabaseHas('ledger_accounts', [
        'organization_id' => $organization->id,
        'code' => '1000',
        'name' => '現金及び預金',
        'normal_balance' => 'debit',
    ]);
    $this->assertDatabaseHas('ledger_accounts', [
        'organization_id' => $organization->id,
        'code' => '1300',
        'name' => '貸倒引当金',
        'account_type' => 'asset',
        'normal_balance' => 'credit',
    ]);
    foreach ([
        '4000' => '売上',
        '4010' => '製品売上',
        '4020' => '商品及び製品売上',
        '4100' => 'サービス売上',
    ] as $code => $name) {
        $this->assertDatabaseHas('ledger_accounts', [
            'organization_id' => $organization->id,
            'code' => $code,
            'name' => $name,
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
        ]);
    }
    foreach ([
        '5000' => '売上原価',
        '5010' => '製品売上原価',
        '5020' => '商品及び製品売上原価',
        '5030' => '役務原価',
    ] as $code => $name) {
        $this->assertDatabaseHas('ledger_accounts', [
            'organization_id' => $organization->id,
            'code' => $code,
            'name' => $name,
            'account_type' => 'expense',
            'normal_balance' => 'debit',
        ]);
    }
    $this->assertDatabaseHas('ledger_accounts', [
        'organization_id' => $organization->id,
        'code' => '2510',
        'name' => '資産除去債務',
        'account_type' => 'liability',
    ]);
    $this->assertDatabaseHas('ledger_accounts', [
        'organization_id' => $organization->id,
        'code' => '3300',
        'name' => '自己株式',
        'account_type' => 'equity',
        'normal_balance' => 'debit',
    ]);
    $this->assertDatabaseHas('ledger_accounts', [
        'organization_id' => $organization->id,
        'code' => '4210',
        'name' => '受取利息及び配当金',
        'account_type' => 'revenue',
    ]);
    foreach ([
        '4190' => 'その他の営業収益',
        '4260' => '雑収益',
        '4290' => 'その他の営業外収益',
        '4300' => '固定資産売却益',
        '4310' => '有価証券売却益',
        '4390' => 'その他の特別利益',
    ] as $code => $name) {
        $this->assertDatabaseHas('ledger_accounts', [
            'organization_id' => $organization->id,
            'code' => $code,
            'name' => $name,
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
        ]);
    }
    foreach ([
        '6060' => '役員報酬',
        '6140' => 'その他の営業費用',
        '6150' => '広告宣伝費',
        '6160' => '通信費',
        '6170' => '地代家賃',
        '6180' => '消耗品費',
        '6190' => '外注費',
        '7410' => '為替差損',
        '7420' => '雑損失',
        '7490' => 'その他の営業外費用',
        '7500' => '投資有価証券評価損',
        '7520' => '固定資産売却損',
        '7530' => '固定資産除却損',
        '7540' => '有価証券売却損',
        '7550' => '減損損失',
        '7560' => '災害損失',
        '7590' => 'その他の特別損失',
    ] as $code => $name) {
        $this->assertDatabaseHas('ledger_accounts', [
            'organization_id' => $organization->id,
            'code' => $code,
            'name' => $name,
            'account_type' => 'expense',
            'normal_balance' => 'debit',
        ]);
    }
    $this->assertDatabaseHas('ledger_accounts', [
        'organization_id' => $organization->id,
        'code' => '7610',
        'name' => '法人税等調整額',
        'account_type' => 'expense',
    ]);
    $this->assertDatabaseHas('journal_entries', [
        'organization_id' => $organization->id,
        'entry_date' => '2026-04-25',
        'description' => '4月人件費',
    ]);
    $csDepartment = $organization->departments()->where('code', 'D210')->firstOrFail();
    $managementDepartment = $organization->departments()->where('code', 'D100')->firstOrFail();
    $informationSystemsDepartment = $organization->departments()->where('code', 'D130')->firstOrFail();
    $softwareDevelopmentDepartment = $organization->departments()->where('code', 'D310')->firstOrFail();
    $hrDepartment = $organization->departments()->where('code', 'D120')->firstOrFail();
    $domesticSalesDepartment = $organization->departments()->where('code', 'D410')->firstOrFail();
    $softwareLicenseSales = $organization->managementAccounts()->where('code', '4300')->firstOrFail();
    $officerCompensation = $organization->managementAccounts()->where('code', '6060')->firstOrFail();
    $salaryJournalEntry = $organization->journalEntries()->whereDate('entry_date', '2026-04-25')->firstOrFail();
    $employeeSalaryAccount = $organization->ledgerAccounts()->where('code', '6000')->firstOrFail();
    $officerCompensationAccount = $organization->ledgerAccounts()->where('code', '6060')->firstOrFail();

    expect($salaryJournalEntry->originating_department_id)->toBe($hrDepartment->id);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $salaryJournalEntry->id,
        'ledger_account_id' => $employeeSalaryAccount->id,
        'amount' => '200000.00',
        'description' => '4月従業員給与（1名）',
    ]);
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $salaryJournalEntry->id,
        'ledger_account_id' => $officerCompensationAccount->id,
        'amount' => '250000.00',
        'description' => '4月創業者役員報酬',
    ]);

    $this->assertDatabaseHas('monthly_amounts', [
        'organization_id' => $organization->id,
        'department_id' => $softwareDevelopmentDepartment->id,
        'management_account_id' => $softwareLicenseSales->id,
        'period' => '2025-01-01',
        'type' => 'actual',
        'amount' => '250000.00',
        'memo' => '初期サンプル: 自社SaaSのサブスクリプション売上',
    ]);
    $this->assertDatabaseHas('monthly_amounts', [
        'organization_id' => $organization->id,
        'department_id' => $softwareDevelopmentDepartment->id,
        'management_account_id' => $softwareLicenseSales->id,
        'period' => '2026-04-01',
        'type' => 'budget',
        'amount' => '390000.00',
        'memo' => '初期サンプル: 自社SaaSのサブスクリプション売上',
    ]);
    $this->assertDatabaseHas('monthly_amounts', [
        'organization_id' => $organization->id,
        'department_id' => $softwareDevelopmentDepartment->id,
        'management_account_id' => $softwareLicenseSales->id,
        'period' => '2026-04-01',
        'type' => 'actual',
        'amount' => '400000.00',
        'memo' => '初期サンプル: 自社SaaSのサブスクリプション売上',
    ]);
    $this->assertDatabaseHas('monthly_amounts', [
        'organization_id' => $organization->id,
        'department_id' => $managementDepartment->id,
        'management_account_id' => $officerCompensation->id,
        'period' => '2027-03-01',
        'type' => 'budget',
        'amount' => '250000.00',
        'memo' => '初期サンプル: 創業者の役員報酬',
    ]);
    foreach ([
        ['FA-DEV-001', '創業者開発用ワークステーション', $softwareDevelopmentDepartment->id, '480000.00'],
        ['FA-OPS-001', '社員業務用ノートパソコン', $csDepartment->id, '240000.00'],
        ['FA-INF-001', 'バックアップ用NAS・ネットワーク機器', $informationSystemsDepartment->id, '360000.00'],
        ['FA-SW-001', '自社SaaSプラットフォーム', $softwareDevelopmentDepartment->id, '1500000.00'],
        ['FA-RTL-001', '商品A保管・梱包設備', $domesticSalesDepartment->id, '300000.00'],
        ['FA-OFF-001', '事務机・チェア一式', $managementDepartment->id, '320000.00'],
    ] as [$assetCode, $assetName, $departmentId, $acquisitionCost]) {
        $this->assertDatabaseHas('fixed_assets', [
            'organization_id' => $organization->id,
            'department_id' => $departmentId,
            'asset_code' => $assetCode,
            'asset_name' => $assetName,
            'acquisition_cost' => $acquisitionCost,
            'status' => 'held',
        ]);
    }
    expect($organization->monthlyAmounts()->count())->toBe(585);
    expect($organization->monthlyAmounts()->where('type', 'budget')->count())->toBe(360);
    expect($organization->monthlyAmounts()->where('type', 'actual')->count())->toBe(225);
    expect($organization->monthlyAmounts()->where('type', 'actual')->whereDate('period', '>=', '2026-07-01')->exists())->toBeFalse();
    expect($organization->fixedAssets()->count())->toBe(6);
    expect($organization->ledgerAccounts()->count())->toBe(133);
    expect($organization->journalEntries()->count())->toBe(147);

    $priorYearJournalEntries = $organization->journalEntries()
        ->whereBetween('entry_date', ['2025-01-01', '2025-12-31'])
        ->with('lines.ledgerAccount')
        ->get();
    $priorYearJournalEntriesByMonth = $priorYearJournalEntries->groupBy(
        fn (JournalEntry $journalEntry): int => $journalEntry->entry_date->month,
    );

    $journalEntries = $organization->journalEntries()
        ->whereBetween('entry_date', ['2026-01-01', '2026-06-30'])
        ->with('lines.ledgerAccount')
        ->get();
    $journalEntriesByMonth = $journalEntries->groupBy(
        fn (JournalEntry $journalEntry): int => $journalEntry->entry_date->month,
    );

    expect($journalEntries->every(
        fn (JournalEntry $journalEntry): bool => $journalEntry->debitTotal() === $journalEntry->creditTotal(),
    ))->toBeTrue();
    expect($journalEntries->every(
        fn (JournalEntry $journalEntry): bool => filled($journalEntry->notes),
    ))->toBeTrue();
    expect($journalEntries->pluck('notes')->unique()->count())->toBe(51);
    expect($priorYearJournalEntries)->toHaveCount(96);
    expect($priorYearJournalEntries->every(
        fn (JournalEntry $journalEntry): bool => $journalEntry->debitTotal() === $journalEntry->creditTotal(),
    ))->toBeTrue();
    expect($priorYearJournalEntries->every(
        fn (JournalEntry $journalEntry): bool => filled($journalEntry->notes),
    ))->toBeTrue();
    expect($organization->journalEntries()->pluck('notes')->unique()->count())->toBe(147);
    expect($organization->journalEntries()
        ->where('notes', '小規模SaaS・IT支援・小売業の月次サンプル仕訳')
        ->exists())->toBeFalse();
    expect($organization->journalEntries()
        ->where('description', '4月SaaS・広告収入')
        ->value('notes'))->toBe('4月分のSaaS利用料と広告収入について、プラットフォームから普通預金への入金を計上。');
    expect($organization->journalEntries()
        ->where('description', '4月人件費')
        ->value('notes'))->toBe('4月分の従業員給与、創業者の役員報酬および法定福利費を普通預金から支払。');
    expect($organization->journalEntries()
        ->where('description', '4月営業費用')
        ->value('notes'))->toBe('4月分のクラウド利用料、事務所家賃、広告宣伝費、決済・振込手数料、消耗品費および開発外注費を普通預金から支払。');
    $fixedAssetSaleEntry = $organization->journalEntries()
        ->where('description', '旧ネットワーク機器の売却')
        ->firstOrFail();
    $fixedAssetSaleGainAccount = $organization->ledgerAccounts()->where('code', '4300')->firstOrFail();
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $fixedAssetSaleEntry->id,
        'ledger_account_id' => $fixedAssetSaleGainAccount->id,
        'side' => 'credit',
        'amount' => '20000.00',
        'description' => '帳簿価額を上回る売却益',
    ]);
    $investmentImpairmentEntry = $organization->journalEntries()
        ->where('description', '投資有価証券の減損処理')
        ->firstOrFail();
    $investmentSecuritiesValuationLossAccount = $organization->ledgerAccounts()->where('code', '7500')->firstOrFail();
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $investmentImpairmentEntry->id,
        'ledger_account_id' => $investmentSecuritiesValuationLossAccount->id,
        'side' => 'debit',
        'amount' => '30000.00',
        'description' => '減損による投資有価証券評価損',
    ]);

    $monthlyAmounts = $organization->monthlyAmounts()
        ->with('managementAccount')
        ->get();
    $monthlyProfit = fn (string $type): array => $monthlyAmounts
        ->where('type', $type)
        ->groupBy(fn (MonthlyAmount $monthlyAmount): string => $monthlyAmount->period->format('Y-m'))
        ->map(function (Collection $amounts): int {
            return $amounts->reduce(function (int $profit, MonthlyAmount $monthlyAmount): int {
                return match ($monthlyAmount->managementAccount->account_type) {
                    'revenue' => $profit + (int) $monthlyAmount->amount,
                    'expense' => $profit - (int) $monthlyAmount->amount,
                    default => $profit,
                };
            }, 0);
        })
        ->sortKeys()
        ->all();

    expect($monthlyProfit('budget'))->toBe([
        '2025-01' => 5000,
        '2025-02' => 5000,
        '2025-03' => 5000,
        '2025-04' => 5000,
        '2025-05' => 5000,
        '2025-06' => 10000,
        '2025-07' => 10000,
        '2025-08' => 10000,
        '2025-09' => 10000,
        '2025-10' => 10000,
        '2025-11' => 10000,
        '2025-12' => 15000,
        '2026-04' => 295000,
        '2026-05' => 297000,
        '2026-06' => 309000,
        '2026-07' => 298000,
        '2026-08' => 308000,
        '2026-09' => 331000,
        '2026-10' => 302000,
        '2026-11' => 313000,
        '2026-12' => 316000,
        '2027-01' => 314000,
        '2027-02' => 324000,
        '2027-03' => 333000,
    ]);
    expect($monthlyProfit('actual'))->toBe([
        '2025-01' => 10000,
        '2025-02' => -20000,
        '2025-03' => 5000,
        '2025-04' => 15000,
        '2025-05' => -10000,
        '2025-06' => 20000,
        '2025-07' => 0,
        '2025-08' => 10000,
        '2025-09' => 15000,
        '2025-10' => -5000,
        '2025-11' => 25000,
        '2025-12' => 35000,
        '2026-04' => 319000,
        '2026-05' => 325000,
        '2026-06' => 335000,
    ]);

    $priorYearMonthlyProfits = [1 => 10000, 2 => -20000, 3 => 5000, 4 => 15000, 5 => -10000, 6 => 20000, 7 => 0, 8 => 10000, 9 => 15000, 10 => -5000, 11 => 25000, 12 => 35000];

    foreach ($priorYearMonthlyProfits as $month => $expectedProfit) {
        $profit = $priorYearJournalEntriesByMonth[$month]
            ->flatMap(fn (JournalEntry $journalEntry) => $journalEntry->lines)
            ->reduce(function (int $profit, JournalEntryLine $line): int {
                return match ($line->ledgerAccount->account_type) {
                    LedgerAccountType::Revenue => $profit + (int) $line->amount,
                    LedgerAccountType::Expense => $profit - (int) $line->amount,
                    default => $profit,
                };
            }, 0);

        expect($profit)->toBe($expectedProfit);
    }

    expect(array_sum($priorYearMonthlyProfits))->toBe(100000);

    foreach ([1 => 305000, 2 => 323000, 3 => 313000, 4 => 319000, 5 => 325000, 6 => 325000] as $month => $expectedProfit) {
        $profit = $journalEntriesByMonth[$month]
            ->flatMap(fn (JournalEntry $journalEntry) => $journalEntry->lines)
            ->reduce(function (int $profit, JournalEntryLine $line): int {
                return match ($line->ledgerAccount->account_type) {
                    LedgerAccountType::Revenue => $profit + (int) $line->amount,
                    LedgerAccountType::Expense => $profit - (int) $line->amount,
                    default => $profit,
                };
            }, 0);

        expect($profit)->toBe($expectedProfit);
    }

    expect($organization->fixedAssets()->where('asset_code', 'FA-SW-001')->firstOrFail()->bookValue())->toBe('1050000.00');

    $departmentCount = $organization->departments()->count();
    $managementAccountCount = $organization->managementAccounts()->count();
    $monthlyAmountCount = $organization->monthlyAmounts()->count();
    $fixedAssetCount = $organization->fixedAssets()->count();
    $ledgerAccountCount = $organization->ledgerAccounts()->count();
    $journalEntryCount = $organization->journalEntries()->count();
    $adminPasswordHash = $admin->password;
    $companyAdminPasswordHash = $companyAdmin->password;
    $userPasswordHash = $user->password;

    $this->seed();

    $this->assertDatabaseCount('companies', 1);
    $this->assertDatabaseCount('organizations', 1);
    $this->assertDatabaseCount('users', 3);
    expect($organization->departments()->count())->toBe($departmentCount);
    expect($organization->managementAccounts()->count())->toBe($managementAccountCount);
    expect($organization->monthlyAmounts()->count())->toBe($monthlyAmountCount);
    expect($organization->fixedAssets()->count())->toBe($fixedAssetCount);
    expect($organization->ledgerAccounts()->count())->toBe($ledgerAccountCount);
    expect($organization->journalEntries()->count())->toBe($journalEntryCount);
    expect($admin->fresh()->password)->toBe($adminPasswordHash);
    expect($companyAdmin->fresh()->password)->toBe($companyAdminPasswordHash);
    expect($user->fresh()->password)->toBe($userPasswordHash);
});

test('initial tenant seeder preserves an existing user password and role separation', function () {
    $company = Company::factory()->create(['code' => Company::DEFAULT_CODE]);
    $organization = $company->organizations()->create([
        'name' => 'KM',
        'type' => 'company',
    ]);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'organization_id' => $organization->id,
        'user_type' => UserType::User,
        'login_id' => 'user1',
        'name' => 'ユーザー1',
        'password' => Hash::make('existing-secret'),
    ]);
    $passwordHash = $user->password;

    $this->seed(InitialTenantSeeder::class);

    $user->refresh();
    $admin = User::query()->where('login_id', 'admin')->firstOrFail();
    $companyAdmin = User::query()->where('login_id', 'company_admin')->firstOrFail();

    expect($user->login_id)->toBe('user1');
    expect($user->name)->toBe('user1');
    expect($user->user_type)->toBe(UserType::User);
    expect($user->password)->toBe($passwordHash);
    expect($admin->user_type)->toBe(UserType::Admin);
    expect($companyAdmin->user_type)->toBe(UserType::CompanyAdmin);
    $this->assertDatabaseCount('users', 3);
});
