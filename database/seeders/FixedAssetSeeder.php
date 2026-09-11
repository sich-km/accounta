<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class FixedAssetSeeder extends Seeder
{
    /**
     * @var list<array{
     *     asset_code: string,
     *     asset_name: string,
     *     asset_category: string,
     *     asset_category_detail: null,
     *     acquisition_date: string,
     *     service_start_date: string,
     *     acquisition_cost: string,
     *     useful_life_years: int,
     *     depreciation_method: string,
     *     residual_value: string,
     *     current_period_depreciation_expense: string,
     *     accumulated_depreciation: string,
     *     status: string,
     *     notes: string
     * }>
     */
    private const array FIXED_ASSETS = [
        [
            'asset_code' => 'FA-CS-001',
            'asset_name' => '顧客対応用ノートパソコン',
            'asset_category' => 'furniture_fixture',
            'asset_category_detail' => null,
            'acquisition_date' => '2025-04-01',
            'service_start_date' => '2025-04-01',
            'acquisition_cost' => '600000.00',
            'useful_life_years' => 4,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '150000.00',
            'accumulated_depreciation' => '225000.00',
            'status' => 'held',
            'notes' => 'CS統括部の顧客対応業務で使用',
        ],
        [
            'asset_code' => 'FA-CS-002',
            'asset_name' => 'CS業務管理ソフトウェア',
            'asset_category' => 'software',
            'asset_category_detail' => null,
            'acquisition_date' => '2025-01-01',
            'service_start_date' => '2025-01-01',
            'acquisition_cost' => '2400000.00',
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '480000.00',
            'accumulated_depreciation' => '960000.00',
            'status' => 'held',
            'notes' => '問い合わせ・保守案件の管理に使用',
        ],
        [
            'asset_code' => 'FA-CS-003',
            'asset_name' => 'サービス訪問用車両',
            'asset_category' => 'vehicle_transportation',
            'asset_category_detail' => null,
            'acquisition_date' => '2024-07-01',
            'service_start_date' => '2024-07-01',
            'acquisition_cost' => '3600000.00',
            'useful_life_years' => 6,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '600000.00',
            'accumulated_depreciation' => '1350000.00',
            'status' => 'held',
            'notes' => 'オンサイト保守サービスで使用',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::query()
            ->select('id')
            ->eachById(function (Organization $organization): void {
                $csDepartment = Department::query()
                    ->forOrganization($organization->id)
                    ->where('code', 'D210')
                    ->where('name', 'CS統括部')
                    ->where('is_active', true)
                    ->first();

                if ($csDepartment === null) {
                    return;
                }

                foreach (self::FIXED_ASSETS as $fixedAsset) {
                    $organization->fixedAssets()->updateOrCreate(
                        ['asset_code' => $fixedAsset['asset_code']],
                        [
                            'department_id' => $csDepartment->id,
                            ...$fixedAsset,
                        ],
                    );
                }
            });
    }
}
