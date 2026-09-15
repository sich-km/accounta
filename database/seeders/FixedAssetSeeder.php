<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class FixedAssetSeeder extends Seeder
{
    /**
     * @var list<array{
     *     department_code: string,
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
            'department_code' => 'D310',
            'asset_code' => 'FA-DEV-001',
            'asset_name' => '創業者開発用ワークステーション',
            'asset_category' => 'furniture_fixture',
            'asset_category_detail' => null,
            'acquisition_date' => '2025-01-01',
            'service_start_date' => '2025-01-01',
            'acquisition_cost' => '480000.00',
            'useful_life_years' => 4,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '60000.00',
            'accumulated_depreciation' => '180000.00',
            'status' => 'held',
            'notes' => 'SaaS開発および受託システム開発に使用',
        ],
        [
            'department_code' => 'D210',
            'asset_code' => 'FA-OPS-001',
            'asset_name' => '社員業務用ノートパソコン',
            'asset_category' => 'furniture_fixture',
            'asset_category_detail' => null,
            'acquisition_date' => '2025-01-01',
            'service_start_date' => '2025-01-01',
            'acquisition_cost' => '240000.00',
            'useful_life_years' => 4,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '30000.00',
            'accumulated_depreciation' => '90000.00',
            'status' => 'held',
            'notes' => '顧客対応、システム保守および商品販売業務に使用',
        ],
        [
            'department_code' => 'D130',
            'asset_code' => 'FA-INF-001',
            'asset_name' => 'バックアップ用NAS・ネットワーク機器',
            'asset_category' => 'furniture_fixture',
            'asset_category_detail' => null,
            'acquisition_date' => '2025-01-01',
            'service_start_date' => '2025-01-01',
            'acquisition_cost' => '360000.00',
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '36000.00',
            'accumulated_depreciation' => '108000.00',
            'status' => 'held',
            'notes' => 'ソースコード、顧客データおよび社内データの保管に使用',
        ],
        [
            'department_code' => 'D310',
            'asset_code' => 'FA-SW-001',
            'asset_name' => '自社SaaSプラットフォーム',
            'asset_category' => 'software',
            'asset_category_detail' => null,
            'acquisition_date' => '2025-01-01',
            'service_start_date' => '2025-01-01',
            'acquisition_cost' => '1500000.00',
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '150000.00',
            'accumulated_depreciation' => '450000.00',
            'status' => 'held',
            'notes' => 'サブスクリプションおよび広告収益を得る自社開発ソフトウェア',
        ],
        [
            'department_code' => 'D410',
            'asset_code' => 'FA-RTL-001',
            'asset_name' => '商品A保管・梱包設備',
            'asset_category' => 'furniture_fixture',
            'asset_category_detail' => null,
            'acquisition_date' => '2025-01-01',
            'service_start_date' => '2025-01-01',
            'acquisition_cost' => '300000.00',
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '30000.00',
            'accumulated_depreciation' => '90000.00',
            'status' => 'held',
            'notes' => '商品Aの保管、検品および発送準備に使用',
        ],
        [
            'department_code' => 'D100',
            'asset_code' => 'FA-OFF-001',
            'asset_name' => '事務机・チェア一式',
            'asset_category' => 'furniture_fixture',
            'asset_category_detail' => null,
            'acquisition_date' => '2025-01-01',
            'service_start_date' => '2025-01-01',
            'acquisition_cost' => '320000.00',
            'useful_life_years' => 8,
            'depreciation_method' => 'straight_line',
            'residual_value' => '0.00',
            'current_period_depreciation_expense' => '20000.00',
            'accumulated_depreciation' => '60000.00',
            'status' => 'held',
            'notes' => '創業者および社員の執務用家具',
        ],
    ];

    /**
     * @var array<string, array{asset_code: string, asset_name: string}>
     */
    private const array LEGACY_ASSETS = [
        'FA-DEV-001' => [
            'asset_code' => 'FA-CS-001',
            'asset_name' => '顧客対応用ノートパソコン',
        ],
        'FA-SW-001' => [
            'asset_code' => 'FA-CS-002',
            'asset_name' => 'CS業務管理ソフトウェア',
        ],
        'FA-OPS-001' => [
            'asset_code' => 'FA-CS-003',
            'asset_name' => 'サービス訪問用車両',
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
                $departmentIds = $organization->departments()
                    ->where('is_active', true)
                    ->pluck('id', 'code');

                foreach (self::FIXED_ASSETS as $fixedAsset) {
                    $departmentId = $departmentIds->get($fixedAsset['department_code']);

                    if ($departmentId === null) {
                        continue;
                    }

                    $attributes = $fixedAsset;
                    unset($attributes['department_code']);

                    $asset = $organization->fixedAssets()
                        ->where('asset_code', $fixedAsset['asset_code'])
                        ->first();

                    if ($asset === null && isset(self::LEGACY_ASSETS[$fixedAsset['asset_code']])) {
                        $legacyAsset = self::LEGACY_ASSETS[$fixedAsset['asset_code']];
                        $asset = $organization->fixedAssets()
                            ->where('asset_code', $legacyAsset['asset_code'])
                            ->where('asset_name', $legacyAsset['asset_name'])
                            ->first();
                    }

                    if ($asset === null) {
                        $organization->fixedAssets()->create([
                            'department_id' => $departmentId,
                            ...$attributes,
                        ]);

                        continue;
                    }

                    $asset->update([
                        'department_id' => $departmentId,
                        ...$attributes,
                    ]);
                }
            });
    }
}
