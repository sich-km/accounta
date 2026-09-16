<?php

use App\Enums\UserType;
use App\Exports\AccountaWorkbookExport;
use App\Models\Department;
use App\Models\FixedAsset;
use App\Models\JournalDocument;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LedgerAccount;
use App\Models\ManagementAccount;
use App\Models\MonthlyAmount;
use App\Models\Organization;
use App\Models\User;
use App\Services\FiscalYearService;
use App\Services\ProfitAndLossSummaryService;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

test('current organization data can be downloaded as an Excel workbook', function () {
    $organization = Organization::factory()->create([
        'name' => '=OrganizationName',
        'type' => 'company',
    ]);
    $organization->company->update([
        'code' => 'test-company',
        'name' => '@CompanyName',
        'fiscal_year_start_month' => 4,
    ]);
    $user = User::factory()->admin()->for($organization)->create();
    $department = Department::factory()->for($organization)->create([
        'code' => 'D002',
        'name' => '=DepartmentName',
    ]);
    Department::factory()->for($organization)->inactive()->create([
        'code' => 'D001',
        'name' => '無効部門',
    ]);
    $managementAccount = ManagementAccount::factory()->for($organization)->create([
        'code' => 'A002',
        'name' => '+AccountName',
        'account_type' => 'expense',
    ]);
    ManagementAccount::factory()->for($organization)->inactive()->create([
        'code' => 'A001',
        'name' => '無効科目',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'management_account_id' => $managementAccount->id,
        'period' => '2026-04-01',
        'type' => 'budget',
        'amount' => '5000000.25',
        'memo' => '=SUM(1,1)',
    ]);
    MonthlyAmount::factory()->create();
    FixedAsset::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'asset_code' => 'FA-002',
        'asset_name' => '=AssetName',
        'asset_category' => 'other',
        'asset_category_detail' => '+AssetCategory',
        'acquisition_date' => '2026-04-01',
        'service_start_date' => '2026-04-15',
        'acquisition_cost' => '5000000.25',
        'useful_life_years' => 10,
        'depreciation_method' => 'straight_line',
        'residual_value' => '100000.00',
        'current_period_depreciation_expense' => '400000.00',
        'accumulated_depreciation' => '800000.00',
        'status' => 'held',
        'notes' => '=SUM(1,1)',
    ]);
    FixedAsset::factory()->create();
    $cash = LedgerAccount::factory()->for($organization)->create([
        'code' => '1000',
        'name' => '=Cash',
        'account_type' => 'asset',
        'normal_balance' => 'debit',
    ]);
    $sales = LedgerAccount::factory()->for($organization)->create([
        'code' => '4100',
        'name' => '+Sales',
        'account_type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    $journalEntry = JournalEntry::factory()->for($organization)->create([
        'entry_date' => '2026-04-20',
        'description' => '=ServiceSale',
        'notes' => '@ExportNote',
    ]);
    $journalEntry->lines()->createMany([
        ['organization_id' => $organization->id, 'line_number' => 1, 'ledger_account_id' => $cash->id, 'department_id' => null, 'side' => 'debit', 'amount' => '125000.50', 'description' => '-Deposit'],
        ['organization_id' => $organization->id, 'line_number' => 2, 'ledger_account_id' => $sales->id, 'department_id' => $department->id, 'side' => 'credit', 'amount' => '125000.50', 'description' => '+Revenue'],
    ]);
    JournalDocument::factory()->for($journalEntry)->create(['organization_id' => $organization->id]);
    JournalEntryLine::factory()->create();

    $this->travelTo(Carbon::create(2026, 9, 4, 13, 59, 0, 'Asia/Tokyo'));

    $response = $this->actingAs($user)
        ->get(route('export.excel'));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload('accounta_20260904_135900.xlsx');

    $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());
    $this->travelBack();

    expect($spreadsheet->getSheetNames())->toBe([
        'まとめ',
        '仕訳帳',
        '固定資産管理台帳',
        '予実管理',
        '部門マスタ',
        '予実管理科目マスタ',
        '会社・組織情報',
    ]);

    $summary = $spreadsheet->getSheetByName('まとめ');

    expect($summary?->getCell('A2')->getValue())->toBe('対象年度');
    expect($summary?->getCell('B2')->getValue())->toBe('2026年度');
    expect($summary?->getCell('A5')->getValue())->toBe('P/Lまとめ');
    expect($summary?->getCell('B7')->getValue())->toBe(125000.5);
    expect($summary?->getCell('B8')->getValue())->toBe(0.0);
    expect($summary?->getCell('B11')->getValue())->toBe(125000.5);
    expect($summary?->getCell('A13')->getValue())->toBe('B/Sまとめ');
    expect($summary?->getCell('B15')->getValue())->toBe(125000.5);
    expect($summary?->getCell('B18')->getValue())->toBe(0.0);
    expect($summary?->getCell('B19')->getValue())->toBe(125000.5);
    expect($summary?->getCell('A22')->getValue())->toBe('予実まとめ');
    expect($summary?->getCell('B25')->getValue())->toBe(5000000.25);
    expect($summary?->getCell('B26')->getValue())->toBe(-5000000.25);
    expect($summary?->getCell('B27')->getValue())->toBe(1);
    expect($summary?->getCell('C27')->getValue())->toBe(0);
    expect($summary?->getCell('B7')->getStyle()->getNumberFormat()->getFormatCode())->toBe('#,##0.00');

    $amounts = $spreadsheet->getSheetByName('予実管理');

    expect($amounts?->rangeToArray('A1:H1')[0])->toBe([
        'Period',
        'Year',
        'Month',
        'DepartmentCode',
        'ManagementAccountCode',
        'Type',
        'Amount',
        'Memo',
    ]);
    expect($amounts?->getHighestDataRow())->toBe(2);
    expect($amounts?->getCell('A2')->getFormattedValue())->toBe('2026-04');
    expect($amounts?->getCell('B2')->getValue())->toBe(2026);
    expect($amounts?->getCell('C2')->getValue())->toBe(4);
    expect($amounts?->getCell('D2')->getValue())->toBe('D002');
    expect($amounts?->getCell('E2')->getValue())->toBe('A002');
    expect($amounts?->getCell('F2')->getValue())->toBe('budget');
    expect($amounts?->getCell('G2')->getValue())->toBe(5000000.25);
    expect($amounts?->getCell('G2')->getStyle()->getNumberFormat()->getFormatCode())->toBe('#,##0.00');
    expect($amounts?->getCell('H2')->getValue())->toBe('=SUM(1,1)');
    expect($amounts?->getCell('H2')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($amounts?->getColumnDimension('A')->getWidth())->toBe(12.0);
    expect($amounts?->getColumnDimension('H')->getWidth())->toBe(40.0);

    $fixedAssets = $spreadsheet->getSheetByName('固定資産管理台帳');

    expect($fixedAssets?->rangeToArray('A1:P1')[0])->toBe([
        'AssetCode',
        'AssetName',
        'AssetCategory',
        'AssetCategoryDetail',
        'DepartmentCode',
        'AcquisitionDate',
        'ServiceStartDate',
        'AcquisitionCost',
        'UsefulLifeYears',
        'DepreciationMethod',
        'ResidualValue',
        'CurrentPeriodDepreciationExpense',
        'AccumulatedDepreciation',
        'BookValue',
        'Status',
        'Notes',
    ]);
    expect($fixedAssets?->getHighestDataRow())->toBe(2);
    expect($fixedAssets?->getCell('A2')->getValue())->toBe('FA-002');
    expect($fixedAssets?->getCell('B2')->getValue())->toBe('=AssetName');
    expect($fixedAssets?->getCell('B2')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($fixedAssets?->getCell('C2')->getValue())->toBe('other');
    expect($fixedAssets?->getCell('D2')->getValue())->toBe('+AssetCategory');
    expect($fixedAssets?->getCell('D2')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($fixedAssets?->getCell('E2')->getValue())->toBe('D002');
    expect($fixedAssets?->getCell('F2')->getFormattedValue())->toBe('2026-04-01');
    expect($fixedAssets?->getCell('G2')->getFormattedValue())->toBe('2026-04-15');
    expect($fixedAssets?->getCell('H2')->getValue())->toBe(5000000.25);
    expect($fixedAssets?->getCell('I2')->getValue())->toBe(10);
    expect($fixedAssets?->getCell('J2')->getValue())->toBe('straight_line');
    expect($fixedAssets?->getCell('K2')->getValue())->toBe(100000.0);
    expect($fixedAssets?->getCell('L2')->getValue())->toBe(400000.0);
    expect($fixedAssets?->getCell('M2')->getValue())->toBe(800000.0);
    expect($fixedAssets?->getCell('N2')->getValue())->toBe(4200000.25);
    expect($fixedAssets?->getCell('N2')->getStyle()->getNumberFormat()->getFormatCode())->toBe('#,##0.00');
    expect($fixedAssets?->getCell('O2')->getValue())->toBe('held');
    expect($fixedAssets?->getCell('P2')->getValue())->toBe('=SUM(1,1)');
    expect($fixedAssets?->getCell('P2')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($fixedAssets?->getColumnDimension('A')->getWidth())->toBe(18.0);
    expect($fixedAssets?->getColumnDimension('P')->getWidth())->toBe(40.0);

    $journalEntries = $spreadsheet->getSheetByName('仕訳帳');

    expect($journalEntries?->rangeToArray('A1:L1')[0])->toBe([
        'JournalEntryId',
        'EntryDate',
        'Description',
        'LineNumber',
        'LedgerAccountCode',
        'LedgerAccountName',
        'DepartmentCode',
        'DebitAmount',
        'CreditAmount',
        'LineDescription',
        'Notes',
        'DocumentCount',
    ]);
    expect($journalEntries?->getHighestDataRow())->toBe(3);
    expect($journalEntries?->getCell('A2')->getValue())->toBe($journalEntry->id);
    expect($journalEntries?->getCell('B2')->getFormattedValue())->toBe('2026-04-20');
    expect($journalEntries?->getCell('C2')->getValue())->toBe('=ServiceSale');
    expect($journalEntries?->getCell('C2')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($journalEntries?->getCell('E2')->getValue())->toBe('1000');
    expect($journalEntries?->getCell('F2')->getValue())->toBe('=Cash');
    expect($journalEntries?->getCell('H2')->getValue())->toBe(125000.5);
    expect($journalEntries?->getCell('I2')->getValue())->toBeNull();
    expect($journalEntries?->getCell('G3')->getValue())->toBe('D002');
    expect($journalEntries?->getCell('H3')->getValue())->toBeNull();
    expect($journalEntries?->getCell('I3')->getValue())->toBe(125000.5);
    expect($journalEntries?->getCell('L3')->getValue())->toBe(1);

    $departments = $spreadsheet->getSheetByName('部門マスタ');

    expect($departments?->rangeToArray('A1:C1')[0])->toBe([
        'DepartmentCode',
        'DepartmentName',
        'IsActive',
    ]);
    expect($departments?->getHighestDataRow())->toBe(3);
    expect($departments?->getCell('A2')->getValue())->toBe('D001');
    expect($departments?->getCell('A3')->getValue())->toBe('D002');
    expect($departments?->getCell('B3')->getValue())->toBe('=DepartmentName');
    expect($departments?->getCell('B3')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($departments?->getCell('C3')->getDataType())->toBe(DataType::TYPE_BOOL);
    expect($departments?->getColumnDimension('A')->getWidth())->toBe(18.0);
    expect($departments?->getColumnDimension('B')->getWidth())->toBe(32.0);

    $managementAccounts = $spreadsheet->getSheetByName('予実管理科目マスタ');

    expect($managementAccounts?->rangeToArray('A1:D1')[0])->toBe([
        'ManagementAccountCode',
        'ManagementAccountName',
        'AccountType',
        'IsActive',
    ]);
    expect($managementAccounts?->getHighestDataRow())->toBe(3);
    expect($managementAccounts?->getCell('A2')->getValue())->toBe('A001');
    expect($managementAccounts?->getCell('A3')->getValue())->toBe('A002');
    expect($managementAccounts?->getCell('B3')->getValue())->toBe('+AccountName');
    expect($managementAccounts?->getCell('B3')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($managementAccounts?->getCell('D3')->getDataType())->toBe(DataType::TYPE_BOOL);
    expect($managementAccounts?->getColumnDimension('A')->getWidth())->toBe(18.0);
    expect($managementAccounts?->getColumnDimension('B')->getWidth())->toBe(32.0);

    $organizationSheet = $spreadsheet->getSheetByName('会社・組織情報');

    expect($organizationSheet?->rangeToArray('A1:E1')[0])->toBe([
        'CompanyCode',
        'CompanyName',
        'FiscalYearStartMonth',
        'OrganizationName',
        'OrganizationType',
    ]);
    expect($organizationSheet?->getHighestDataRow())->toBe(2);
    expect($organizationSheet?->getCell('A2')->getValue())->toBe('test-company');
    expect($organizationSheet?->getCell('B2')->getValue())->toBe('@CompanyName');
    expect($organizationSheet?->getCell('B2')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($organizationSheet?->getCell('C2')->getValue())->toBe(4);
    expect($organizationSheet?->getCell('D2')->getValue())->toBe('=OrganizationName');
    expect($organizationSheet?->getCell('D2')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($organizationSheet?->getCell('E2')->getValue())->toBe('company');
    expect($organizationSheet?->getColumnDimension('A')->getWidth())->toBe(18.0);
    expect($organizationSheet?->getColumnDimension('B')->getWidth())->toBe(32.0);

    $spreadsheet->disconnectWorksheets();
});

test('organizations without records export all sheets with headings', function () {
    $organization = Organization::factory()->create();
    $this->travelTo(Carbon::create(2026, 9, 4, 13, 59, 0, 'Asia/Tokyo'));
    $contents = Excel::raw(
        new AccountaWorkbookExport(
            $organization->id,
            true,
            app(FiscalYearService::class),
            app(ProfitAndLossSummaryService::class),
        ),
        ExcelWriter::XLSX,
    );
    $temporaryFile = tempnam(sys_get_temp_dir(), 'accounta-export-');
    file_put_contents($temporaryFile, $contents);

    try {
        $spreadsheet = IOFactory::load($temporaryFile);

        expect($spreadsheet->getSheetNames())->toBe([
            'まとめ',
            '仕訳帳',
            '固定資産管理台帳',
            '予実管理',
            '部門マスタ',
            '予実管理科目マスタ',
            '会社・組織情報',
        ]);
        expect($spreadsheet->getSheetByName('まとめ')?->getHighestDataRow())->toBe(27);
        expect($spreadsheet->getSheetByName('まとめ')?->getCell('B7')->getValue())->toBe(0.0);
        expect($spreadsheet->getSheetByName('仕訳帳')?->getHighestDataRow())->toBe(1);
        expect($spreadsheet->getSheetByName('固定資産管理台帳')?->getHighestDataRow())->toBe(1);
        expect($spreadsheet->getSheetByName('予実管理')?->getHighestDataRow())->toBe(1);
        expect($spreadsheet->getSheetByName('部門マスタ')?->getHighestDataRow())->toBe(1);
        expect($spreadsheet->getSheetByName('予実管理科目マスタ')?->getHighestDataRow())->toBe(1);
        expect($spreadsheet->getSheetByName('会社・組織情報')?->getHighestDataRow())->toBe(2);

        $spreadsheet->disconnectWorksheets();
    } finally {
        $this->travelBack();
        unlink($temporaryFile);
    }
});

test('non-admin workbooks omit company and organization information', function (UserType $userType) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['user_type' => $userType]);

    $response = $this->actingAs($user)
        ->get(route('export.excel'))
        ->assertOk();

    $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());

    expect($spreadsheet->getSheetNames())->toBe([
        'まとめ',
        '仕訳帳',
        '固定資産管理台帳',
        '予実管理',
        '部門マスタ',
        '予実管理科目マスタ',
    ]);
    expect($spreadsheet->getSheetByName('会社・組織情報'))->toBeNull();

    $spreadsheet->disconnectWorksheets();
})->with([
    'company administrator' => UserType::CompanyAdmin,
    'regular user' => UserType::User,
]);

test('guests cannot download an Excel workbook', function () {
    $this->get(route('export.excel'))
        ->assertRedirect(route('login'));
});
