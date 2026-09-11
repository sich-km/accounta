<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ManagementAccount;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class MonthlyAmountSeeder extends Seeder
{
    /**
     * @var list<array{
     *     department_code: string,
     *     management_account_code: string,
     *     period: string,
     *     budget: string,
     *     actual: string,
     *     memo: string
     * }>
     */
    private const array MONTHLY_AMOUNTS = [
        ['department_code' => 'D410', 'management_account_code' => '4000', 'period' => '2026-04-01', 'budget' => '8000000.00', 'actual' => '8200000.00', 'memo' => '国内営業部 製品売上高'],
        ['department_code' => 'D410', 'management_account_code' => '1100', 'period' => '2026-04-01', 'budget' => '3000000.00', 'actual' => '3100000.00', 'memo' => '国内営業部 売掛金'],
        ['department_code' => 'D500', 'management_account_code' => '1200', 'period' => '2026-04-01', 'budget' => '2400000.00', 'actual' => '2500000.00', 'memo' => '生産部門 棚卸資産'],
        ['department_code' => 'D500', 'management_account_code' => '5200', 'period' => '2026-04-01', 'budget' => '1200000.00', 'actual' => '1260000.00', 'memo' => '生産部門 材料費'],
        ['department_code' => 'D500', 'management_account_code' => '2000', 'period' => '2026-04-01', 'budget' => '1000000.00', 'actual' => '1050000.00', 'memo' => '生産部門 買掛金'],
        ['department_code' => 'D500', 'management_account_code' => '1520', 'period' => '2026-05-01', 'budget' => '5000000.00', 'actual' => '4800000.00', 'memo' => '生産部門 機械装置'],
        ['department_code' => 'D110', 'management_account_code' => '1000', 'period' => '2026-05-01', 'budget' => '4000000.00', 'actual' => '4250000.00', 'memo' => '経理部 現金及び預金'],
        ['department_code' => 'D110', 'management_account_code' => '2400', 'period' => '2026-05-01', 'budget' => '2000000.00', 'actual' => '2000000.00', 'memo' => '経理部 借入金'],
        ['department_code' => 'D310', 'management_account_code' => '7100', 'period' => '2026-05-01', 'budget' => '1800000.00', 'actual' => '1950000.00', 'memo' => 'ソフトウェア開発部 研究開発費'],
        ['department_code' => 'D210', 'management_account_code' => '4200', 'period' => '2026-05-01', 'budget' => '2500000.00', 'actual' => '2650000.00', 'memo' => 'CS統括部 保守サービス売上高'],
    ];

    public function run(): void
    {
        Organization::query()
            ->select('id')
            ->eachById(function (Organization $organization): void {
                $departmentIds = Department::query()
                    ->forOrganization($organization->id)
                    ->whereIn('code', array_column(self::MONTHLY_AMOUNTS, 'department_code'))
                    ->pluck('id', 'code');
                $managementAccountIds = ManagementAccount::query()
                    ->forOrganization($organization->id)
                    ->whereIn('code', array_column(self::MONTHLY_AMOUNTS, 'management_account_code'))
                    ->pluck('id', 'code');

                foreach (self::MONTHLY_AMOUNTS as $monthlyAmount) {
                    $departmentId = $departmentIds->get($monthlyAmount['department_code']);
                    $managementAccountId = $managementAccountIds->get($monthlyAmount['management_account_code']);

                    if ($departmentId === null || $managementAccountId === null) {
                        continue;
                    }

                    foreach (['budget', 'actual'] as $type) {
                        $organization->monthlyAmounts()->updateOrCreate(
                            [
                                'department_id' => $departmentId,
                                'management_account_id' => $managementAccountId,
                                'period' => $monthlyAmount['period'],
                                'type' => $type,
                                'memo' => '初期サンプル: '.$monthlyAmount['memo'],
                            ],
                            ['amount' => $monthlyAmount[$type]],
                        );
                    }
                }
            });
    }
}
