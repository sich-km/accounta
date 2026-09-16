<?php

namespace Database\Seeders;

use App\Models\BudgetActualAccount;
use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class BudgetActualEntrySeeder extends Seeder
{
    private const string SAMPLE_MEMO_PREFIX = '初期サンプル: ';

    /**
     * @var array<string, array{department_code: string, budget_actual_account_code: string, memo: string}>
     */
    private const array LINE_DEFINITIONS = [
        'saas' => ['department_code' => 'D310', 'budget_actual_account_code' => '4300', 'memo' => '自社SaaSのサブスクリプション売上'],
        'advertising_revenue' => ['department_code' => 'D310', 'budget_actual_account_code' => '4900', 'memo' => '自社SaaS内の広告売上'],
        'development' => ['department_code' => 'D310', 'budget_actual_account_code' => '4100', 'memo' => '受託システム開発売上'],
        'maintenance' => ['department_code' => 'D210', 'budget_actual_account_code' => '4200', 'memo' => '顧客システムの保守売上'],
        'retail_sales' => ['department_code' => 'D410', 'budget_actual_account_code' => '4000', 'memo' => '商品Aの販売売上'],
        'purchases' => ['department_code' => 'D410', 'budget_actual_account_code' => '5000', 'memo' => '商品Aの売上原価'],
        'employee_salary' => ['department_code' => 'D120', 'budget_actual_account_code' => '6000', 'memo' => '社員1名の給与'],
        'officer_compensation' => ['department_code' => 'D100', 'budget_actual_account_code' => '6060', 'memo' => '創業者の役員報酬'],
        'legal_welfare' => ['department_code' => 'D120', 'budget_actual_account_code' => '6020', 'memo' => '会社負担の法定福利費'],
        'cloud' => ['department_code' => 'D310', 'budget_actual_account_code' => '6200', 'memo' => 'SaaS・開発環境のクラウド利用料'],
        'rent' => ['department_code' => 'D100', 'budget_actual_account_code' => '6500', 'memo' => '事務所の賃借料'],
        'marketing' => ['department_code' => 'D410', 'budget_actual_account_code' => '6700', 'memo' => 'SaaS・商品Aの広告宣伝費'],
        'payment_fees' => ['department_code' => 'D110', 'budget_actual_account_code' => '6800', 'memo' => '決済・振込手数料'],
        'consumables' => ['department_code' => 'D100', 'budget_actual_account_code' => '6400', 'memo' => '事務用品・梱包資材等の消耗品費'],
        'outsourcing' => ['department_code' => 'D310', 'budget_actual_account_code' => '6100', 'memo' => '受託開発等の外注費'],
    ];

    /**
     * @var list<array{period: string, saas: int, advertising_revenue: int, development: int, maintenance: int, retail_sales: int, purchases: int, employee_salary: int, officer_compensation: int, legal_welfare: int, cloud: int, rent: int, marketing: int, payment_fees: int, consumables: int, outsourcing: int}>
     */
    private const array BUDGETS = [
        ['period' => '2025-01-01', 'saas' => 245000, 'advertising_revenue' => 38000, 'development' => 310000, 'maintenance' => 88000, 'retail_sales' => 215000, 'purchases' => 129000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 54000, 'rent' => 60000, 'marketing' => 24000, 'payment_fees' => 12000, 'consumables' => 16000, 'outsourcing' => 76000],
        ['period' => '2025-02-01', 'saas' => 250000, 'advertising_revenue' => 39000, 'development' => 300000, 'maintenance' => 89000, 'retail_sales' => 220000, 'purchases' => 132000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 55000, 'rent' => 60000, 'marketing' => 25000, 'payment_fees' => 12000, 'consumables' => 16000, 'outsourcing' => 73000],
        ['period' => '2025-03-01', 'saas' => 255000, 'advertising_revenue' => 40000, 'development' => 315000, 'maintenance' => 90000, 'retail_sales' => 225000, 'purchases' => 135000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 56000, 'rent' => 60000, 'marketing' => 25000, 'payment_fees' => 13000, 'consumables' => 16000, 'outsourcing' => 95000],
        ['period' => '2025-04-01', 'saas' => 260000, 'advertising_revenue' => 41000, 'development' => 325000, 'maintenance' => 92000, 'retail_sales' => 230000, 'purchases' => 138000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 57000, 'rent' => 60000, 'marketing' => 26000, 'payment_fees' => 13000, 'consumables' => 17000, 'outsourcing' => 112000],
        ['period' => '2025-05-01', 'saas' => 265000, 'advertising_revenue' => 42000, 'development' => 320000, 'maintenance' => 94000, 'retail_sales' => 235000, 'purchases' => 141000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 58000, 'rent' => 60000, 'marketing' => 27000, 'payment_fees' => 13000, 'consumables' => 17000, 'outsourcing' => 115000],
        ['period' => '2025-06-01', 'saas' => 270000, 'advertising_revenue' => 43000, 'development' => 335000, 'maintenance' => 96000, 'retail_sales' => 240000, 'purchases' => 144000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 59000, 'rent' => 60000, 'marketing' => 28000, 'payment_fees' => 14000, 'consumables' => 17000, 'outsourcing' => 132000],
        ['period' => '2025-07-01', 'saas' => 275000, 'advertising_revenue' => 44000, 'development' => 340000, 'maintenance' => 98000, 'retail_sales' => 245000, 'purchases' => 147000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 60000, 'rent' => 60000, 'marketing' => 28000, 'payment_fees' => 14000, 'consumables' => 18000, 'outsourcing' => 145000],
        ['period' => '2025-08-01', 'saas' => 280000, 'advertising_revenue' => 45000, 'development' => 350000, 'maintenance' => 100000, 'retail_sales' => 250000, 'purchases' => 150000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 61000, 'rent' => 60000, 'marketing' => 29000, 'payment_fees' => 14000, 'consumables' => 18000, 'outsourcing' => 163000],
        ['period' => '2025-09-01', 'saas' => 285000, 'advertising_revenue' => 46000, 'development' => 360000, 'maintenance' => 102000, 'retail_sales' => 255000, 'purchases' => 153000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 62000, 'rent' => 60000, 'marketing' => 30000, 'payment_fees' => 15000, 'consumables' => 18000, 'outsourcing' => 180000],
        ['period' => '2025-10-01', 'saas' => 290000, 'advertising_revenue' => 47000, 'development' => 370000, 'maintenance' => 104000, 'retail_sales' => 260000, 'purchases' => 156000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 63000, 'rent' => 60000, 'marketing' => 30000, 'payment_fees' => 15000, 'consumables' => 19000, 'outsourcing' => 198000],
        ['period' => '2025-11-01', 'saas' => 295000, 'advertising_revenue' => 48000, 'development' => 380000, 'maintenance' => 106000, 'retail_sales' => 265000, 'purchases' => 159000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 64000, 'rent' => 60000, 'marketing' => 31000, 'payment_fees' => 16000, 'consumables' => 19000, 'outsourcing' => 215000],
        ['period' => '2025-12-01', 'saas' => 300000, 'advertising_revenue' => 50000, 'development' => 400000, 'maintenance' => 108000, 'retail_sales' => 280000, 'purchases' => 168000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 66000, 'rent' => 60000, 'marketing' => 34000, 'payment_fees' => 17000, 'consumables' => 20000, 'outsourcing' => 238000],
        ['period' => '2026-04-01', 'saas' => 390000, 'advertising_revenue' => 70000, 'development' => 380000, 'maintenance' => 145000, 'retail_sales' => 350000, 'purchases' => 210000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 75000, 'rent' => 60000, 'marketing' => 35000, 'payment_fees' => 18000, 'consumables' => 22000, 'outsourcing' => 100000],
        ['period' => '2026-05-01', 'saas' => 410000, 'advertising_revenue' => 75000, 'development' => 400000, 'maintenance' => 155000, 'retail_sales' => 370000, 'purchases' => 222000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 80000, 'rent' => 60000, 'marketing' => 38000, 'payment_fees' => 19000, 'consumables' => 24000, 'outsourcing' => 150000],
        ['period' => '2026-06-01', 'saas' => 440000, 'advertising_revenue' => 80000, 'development' => 420000, 'maintenance' => 165000, 'retail_sales' => 390000, 'purchases' => 234000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 83000, 'rent' => 60000, 'marketing' => 42000, 'payment_fees' => 21000, 'consumables' => 26000, 'outsourcing' => 200000],
        ['period' => '2026-07-01', 'saas' => 460000, 'advertising_revenue' => 85000, 'development' => 400000, 'maintenance' => 170000, 'retail_sales' => 410000, 'purchases' => 246000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 86000, 'rent' => 60000, 'marketing' => 45000, 'payment_fees' => 22000, 'consumables' => 28000, 'outsourcing' => 220000],
        ['period' => '2026-08-01', 'saas' => 480000, 'advertising_revenue' => 90000, 'development' => 420000, 'maintenance' => 175000, 'retail_sales' => 430000, 'purchases' => 258000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 88000, 'rent' => 60000, 'marketing' => 48000, 'payment_fees' => 23000, 'consumables' => 30000, 'outsourcing' => 260000],
        ['period' => '2026-09-01', 'saas' => 500000, 'advertising_revenue' => 95000, 'development' => 450000, 'maintenance' => 180000, 'retail_sales' => 450000, 'purchases' => 270000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 90000, 'rent' => 60000, 'marketing' => 50000, 'payment_fees' => 24000, 'consumables' => 30000, 'outsourcing' => 300000],
        ['period' => '2026-10-01', 'saas' => 520000, 'advertising_revenue' => 100000, 'development' => 430000, 'maintenance' => 185000, 'retail_sales' => 470000, 'purchases' => 282000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 92000, 'rent' => 60000, 'marketing' => 52000, 'payment_fees' => 25000, 'consumables' => 32000, 'outsourcing' => 340000],
        ['period' => '2026-11-01', 'saas' => 540000, 'advertising_revenue' => 105000, 'development' => 450000, 'maintenance' => 190000, 'retail_sales' => 500000, 'purchases' => 300000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 95000, 'rent' => 60000, 'marketing' => 55000, 'payment_fees' => 27000, 'consumables' => 35000, 'outsourcing' => 380000],
        ['period' => '2026-12-01', 'saas' => 570000, 'advertising_revenue' => 115000, 'development' => 480000, 'maintenance' => 200000, 'retail_sales' => 540000, 'purchases' => 324000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 100000, 'rent' => 60000, 'marketing' => 65000, 'payment_fees' => 30000, 'consumables' => 40000, 'outsourcing' => 450000],
        ['period' => '2027-01-01', 'saas' => 600000, 'advertising_revenue' => 120000, 'development' => 460000, 'maintenance' => 205000, 'retail_sales' => 500000, 'purchases' => 300000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 102000, 'rent' => 60000, 'marketing' => 55000, 'payment_fees' => 29000, 'consumables' => 35000, 'outsourcing' => 470000],
        ['period' => '2027-02-01', 'saas' => 630000, 'advertising_revenue' => 125000, 'development' => 480000, 'maintenance' => 210000, 'retail_sales' => 520000, 'purchases' => 312000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 105000, 'rent' => 60000, 'marketing' => 58000, 'payment_fees' => 30000, 'consumables' => 36000, 'outsourcing' => 520000],
        ['period' => '2027-03-01', 'saas' => 680000, 'advertising_revenue' => 140000, 'development' => 550000, 'maintenance' => 220000, 'retail_sales' => 580000, 'purchases' => 348000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 110000, 'rent' => 60000, 'marketing' => 70000, 'payment_fees' => 34000, 'consumables' => 45000, 'outsourcing' => 650000],
    ];

    /**
     * @var list<array{period: string, saas: int, advertising_revenue: int, development: int, maintenance: int, retail_sales: int, purchases: int, employee_salary: int, officer_compensation: int, legal_welfare: int, cloud: int, rent: int, marketing: int, payment_fees: int, consumables: int, outsourcing: int}>
     */
    private const array ACTUALS = [
        ['period' => '2025-01-01', 'saas' => 250000, 'advertising_revenue' => 40000, 'development' => 300000, 'maintenance' => 90000, 'retail_sales' => 220000, 'purchases' => 132000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 55000, 'rent' => 60000, 'marketing' => 25000, 'payment_fees' => 12000, 'consumables' => 16000, 'outsourcing' => 70000],
        ['period' => '2025-02-01', 'saas' => 255000, 'advertising_revenue' => 40000, 'development' => 280000, 'maintenance' => 90000, 'retail_sales' => 210000, 'purchases' => 126000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 56000, 'rent' => 60000, 'marketing' => 24000, 'payment_fees' => 12000, 'consumables' => 16000, 'outsourcing' => 81000],
        ['period' => '2025-03-01', 'saas' => 260000, 'advertising_revenue' => 42000, 'development' => 310000, 'maintenance' => 92000, 'retail_sales' => 225000, 'purchases' => 135000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 57000, 'rent' => 60000, 'marketing' => 25000, 'payment_fees' => 13000, 'consumables' => 16000, 'outsourcing' => 98000],
        ['period' => '2025-04-01', 'saas' => 265000, 'advertising_revenue' => 42000, 'development' => 320000, 'maintenance' => 94000, 'retail_sales' => 230000, 'purchases' => 138000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 58000, 'rent' => 60000, 'marketing' => 26000, 'payment_fees' => 13000, 'consumables' => 17000, 'outsourcing' => 104000],
        ['period' => '2025-05-01', 'saas' => 270000, 'advertising_revenue' => 43000, 'development' => 290000, 'maintenance' => 95000, 'retail_sales' => 220000, 'purchases' => 132000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 59000, 'rent' => 60000, 'marketing' => 27000, 'payment_fees' => 13000, 'consumables' => 17000, 'outsourcing' => 100000],
        ['period' => '2025-06-01', 'saas' => 275000, 'advertising_revenue' => 44000, 'development' => 330000, 'maintenance' => 96000, 'retail_sales' => 235000, 'purchases' => 141000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 60000, 'rent' => 60000, 'marketing' => 28000, 'payment_fees' => 14000, 'consumables' => 17000, 'outsourcing' => 120000],
        ['period' => '2025-07-01', 'saas' => 280000, 'advertising_revenue' => 45000, 'development' => 300000, 'maintenance' => 98000, 'retail_sales' => 230000, 'purchases' => 138000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 61000, 'rent' => 60000, 'marketing' => 28000, 'payment_fees' => 14000, 'consumables' => 18000, 'outsourcing' => 114000],
        ['period' => '2025-08-01', 'saas' => 285000, 'advertising_revenue' => 46000, 'development' => 340000, 'maintenance' => 100000, 'retail_sales' => 240000, 'purchases' => 144000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 62000, 'rent' => 60000, 'marketing' => 29000, 'payment_fees' => 14000, 'consumables' => 18000, 'outsourcing' => 154000],
        ['period' => '2025-09-01', 'saas' => 290000, 'advertising_revenue' => 47000, 'development' => 350000, 'maintenance' => 102000, 'retail_sales' => 245000, 'purchases' => 147000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 63000, 'rent' => 60000, 'marketing' => 30000, 'payment_fees' => 15000, 'consumables' => 18000, 'outsourcing' => 166000],
        ['period' => '2025-10-01', 'saas' => 295000, 'advertising_revenue' => 48000, 'development' => 320000, 'maintenance' => 104000, 'retail_sales' => 240000, 'purchases' => 144000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 64000, 'rent' => 60000, 'marketing' => 30000, 'payment_fees' => 15000, 'consumables' => 19000, 'outsourcing' => 160000],
        ['period' => '2025-11-01', 'saas' => 300000, 'advertising_revenue' => 50000, 'development' => 360000, 'maintenance' => 105000, 'retail_sales' => 250000, 'purchases' => 150000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 65000, 'rent' => 60000, 'marketing' => 31000, 'payment_fees' => 16000, 'consumables' => 19000, 'outsourcing' => 179000],
        ['period' => '2025-12-01', 'saas' => 310000, 'advertising_revenue' => 52000, 'development' => 390000, 'maintenance' => 108000, 'retail_sales' => 270000, 'purchases' => 162000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 68000, 'rent' => 60000, 'marketing' => 35000, 'payment_fees' => 17000, 'consumables' => 20000, 'outsourcing' => 213000],
        ['period' => '2026-04-01', 'saas' => 400000, 'advertising_revenue' => 75000, 'development' => 390000, 'maintenance' => 150000, 'retail_sales' => 360000, 'purchases' => 216000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 78000, 'rent' => 60000, 'marketing' => 38000, 'payment_fees' => 19000, 'consumables' => 25000, 'outsourcing' => 100000],
        ['period' => '2026-05-01', 'saas' => 420000, 'advertising_revenue' => 80000, 'development' => 410000, 'maintenance' => 160000, 'retail_sales' => 380000, 'purchases' => 228000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 82000, 'rent' => 60000, 'marketing' => 40000, 'payment_fees' => 20000, 'consumables' => 25000, 'outsourcing' => 150000],
        ['period' => '2026-06-01', 'saas' => 450000, 'advertising_revenue' => 85000, 'development' => 430000, 'maintenance' => 170000, 'retail_sales' => 400000, 'purchases' => 240000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 85000, 'rent' => 60000, 'marketing' => 45000, 'payment_fees' => 22000, 'consumables' => 28000, 'outsourcing' => 200000],
        ['period' => '2026-07-01', 'saas' => 470000, 'advertising_revenue' => 88000, 'development' => 410000, 'maintenance' => 172000, 'retail_sales' => 405000, 'purchases' => 243000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 87000, 'rent' => 60000, 'marketing' => 46000, 'payment_fees' => 23000, 'consumables' => 29000, 'outsourcing' => 230000],
        ['period' => '2026-08-01', 'saas' => 490000, 'advertising_revenue' => 92000, 'development' => 430000, 'maintenance' => 178000, 'retail_sales' => 425000, 'purchases' => 255000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 90000, 'rent' => 60000, 'marketing' => 49000, 'payment_fees' => 24000, 'consumables' => 30000, 'outsourcing' => 270000],
        ['period' => '2026-09-01', 'saas' => 520000, 'advertising_revenue' => 100000, 'development' => 460000, 'maintenance' => 185000, 'retail_sales' => 460000, 'purchases' => 276000, 'employee_salary' => 200000, 'officer_compensation' => 250000, 'legal_welfare' => 70000, 'cloud' => 94000, 'rent' => 60000, 'marketing' => 52000, 'payment_fees' => 25000, 'consumables' => 32000, 'outsourcing' => 330000],
    ];

    public function run(): void
    {
        Organization::query()
            ->select('id')
            ->eachById(function (Organization $organization): void {
                $departmentIds = Department::query()
                    ->forOrganization($organization->id)
                    ->whereIn('code', array_column(self::LINE_DEFINITIONS, 'department_code'))
                    ->pluck('id', 'code');
                $budgetActualAccountIds = BudgetActualAccount::query()
                    ->forOrganization($organization->id)
                    ->whereIn('code', array_column(self::LINE_DEFINITIONS, 'budget_actual_account_code'))
                    ->pluck('id', 'code');
                $seededBudgetActualEntryIds = [];

                foreach (['budget' => self::BUDGETS, 'actual' => self::ACTUALS] as $type => $periodEntries) {
                    foreach ($periodEntries as $periodEntry) {
                        foreach (self::LINE_DEFINITIONS as $amountKey => $lineDefinition) {
                            $departmentId = $departmentIds->get($lineDefinition['department_code']);
                            $budgetActualAccountId = $budgetActualAccountIds->get($lineDefinition['budget_actual_account_code']);

                            if ($departmentId === null || $budgetActualAccountId === null) {
                                continue;
                            }

                            $seededBudgetActualEntry = $organization->budgetActualEntries()->updateOrCreate(
                                [
                                    'department_id' => $departmentId,
                                    'budget_actual_account_id' => $budgetActualAccountId,
                                    'period' => $periodEntry['period'],
                                    'type' => $type,
                                    'memo' => self::SAMPLE_MEMO_PREFIX.$lineDefinition['memo'],
                                ],
                                [
                                    'amount' => number_format($periodEntry[$amountKey], 2, '.', ''),
                                ],
                            );
                            $seededBudgetActualEntryIds[] = $seededBudgetActualEntry->id;
                        }
                    }
                }

                $organization->budgetActualEntries()
                    ->where('memo', 'like', self::SAMPLE_MEMO_PREFIX.'%')
                    ->whereNotIn('id', $seededBudgetActualEntryIds)
                    ->delete();
            });
    }
}
