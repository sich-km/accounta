<?php

use App\Exports\AccountaWorkbookExport;
use App\Models\Account;
use App\Models\Department;
use App\Models\MonthlyAmount;
use App\Models\Organization;
use App\Models\User;
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
    $user = User::factory()->for($organization)->create();
    $department = Department::factory()->for($organization)->create([
        'code' => 'D002',
        'name' => '=DepartmentName',
    ]);
    Department::factory()->for($organization)->inactive()->create([
        'code' => 'D001',
        'name' => '無効部門',
    ]);
    $account = Account::factory()->for($organization)->create([
        'code' => 'A002',
        'name' => '+AccountName',
        'account_type' => 'expense',
    ]);
    Account::factory()->for($organization)->inactive()->create([
        'code' => 'A001',
        'name' => '無効科目',
    ]);
    MonthlyAmount::factory()->create([
        'organization_id' => $organization->id,
        'department_id' => $department->id,
        'account_id' => $account->id,
        'period' => '2026-04-01',
        'type' => 'budget',
        'amount' => '5000000.25',
        'memo' => '=SUM(1,1)',
    ]);
    MonthlyAmount::factory()->create();

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
        'Amounts',
        'Departments',
        'Accounts',
        'Organization',
    ]);

    $amounts = $spreadsheet->getSheetByName('Amounts');

    expect($amounts?->rangeToArray('A1:H1')[0])->toBe([
        'Period',
        'Year',
        'Month',
        'DepartmentCode',
        'AccountCode',
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

    $departments = $spreadsheet->getSheetByName('Departments');

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

    $accounts = $spreadsheet->getSheetByName('Accounts');

    expect($accounts?->rangeToArray('A1:D1')[0])->toBe([
        'AccountCode',
        'AccountName',
        'AccountType',
        'IsActive',
    ]);
    expect($accounts?->getHighestDataRow())->toBe(3);
    expect($accounts?->getCell('A2')->getValue())->toBe('A001');
    expect($accounts?->getCell('A3')->getValue())->toBe('A002');
    expect($accounts?->getCell('B3')->getValue())->toBe('+AccountName');
    expect($accounts?->getCell('B3')->getDataType())->toBe(DataType::TYPE_STRING);
    expect($accounts?->getCell('D3')->getDataType())->toBe(DataType::TYPE_BOOL);

    $organizationSheet = $spreadsheet->getSheetByName('Organization');

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

    $spreadsheet->disconnectWorksheets();
});

test('organizations without records export all sheets with headings', function () {
    $organization = Organization::factory()->create();
    $contents = Excel::raw(
        new AccountaWorkbookExport($organization->id),
        ExcelWriter::XLSX,
    );
    $temporaryFile = tempnam(sys_get_temp_dir(), 'accounta-export-');
    file_put_contents($temporaryFile, $contents);

    try {
        $spreadsheet = IOFactory::load($temporaryFile);

        expect($spreadsheet->getSheetNames())->toBe([
            'Amounts',
            'Departments',
            'Accounts',
            'Organization',
        ]);
        expect($spreadsheet->getSheetByName('Amounts')?->getHighestDataRow())->toBe(1);
        expect($spreadsheet->getSheetByName('Departments')?->getHighestDataRow())->toBe(1);
        expect($spreadsheet->getSheetByName('Accounts')?->getHighestDataRow())->toBe(1);
        expect($spreadsheet->getSheetByName('Organization')?->getHighestDataRow())->toBe(2);

        $spreadsheet->disconnectWorksheets();
    } finally {
        unlink($temporaryFile);
    }
});

test('guests cannot download an Excel workbook', function () {
    $this->get(route('export.excel'))
        ->assertRedirect(route('login'));
});
