<?php

use App\Enums\LedgerAccountType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Database\Seeders\InitialTenantSeeder;
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
    $productSales = $organization->managementAccounts()->where('code', '4000')->firstOrFail();
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
        'department_id' => $domesticSalesDepartment->id,
        'management_account_id' => $productSales->id,
        'period' => '2026-04-01',
        'type' => 'budget',
        'amount' => '8000000.00',
    ]);
    $this->assertDatabaseHas('monthly_amounts', [
        'organization_id' => $organization->id,
        'department_id' => $domesticSalesDepartment->id,
        'management_account_id' => $productSales->id,
        'period' => '2026-04-01',
        'type' => 'actual',
        'amount' => '8200000.00',
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
    expect($organization->monthlyAmounts()->count())->toBe(20);
    expect($organization->fixedAssets()->count())->toBe(6);
    expect($organization->ledgerAccounts()->count())->toBe(133);
    expect($organization->journalEntries()->count())->toBe(49);

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

    foreach ([1 => 305000, 2 => 323000, 3 => 313000, 4 => 319000, 5 => 325000, 6 => 335000] as $month => $expectedProfit) {
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
