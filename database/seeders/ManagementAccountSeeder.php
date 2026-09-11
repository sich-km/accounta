<?php

namespace Database\Seeders;

use App\Models\ManagementAccount;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class ManagementAccountSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const array OBSOLETE_ACCOUNT_CODES = [
        '1500',
    ];

    /**
     * @var list<array{code: string, name: string, account_type: string}>
     */
    private const array ACCOUNTS = [
        ['code' => '1000', 'name' => '現金及び預金', 'account_type' => 'asset'],
        ['code' => '1100', 'name' => '売掛金', 'account_type' => 'asset'],
        ['code' => '1200', 'name' => '棚卸資産', 'account_type' => 'asset'],
        ['code' => '1300', 'name' => '前払費用', 'account_type' => 'asset'],
        ['code' => '1400', 'name' => '未収入金', 'account_type' => 'asset'],
        ['code' => '1510', 'name' => '建物', 'account_type' => 'asset'],
        ['code' => '1511', 'name' => '構築物', 'account_type' => 'asset'],
        ['code' => '1520', 'name' => '機械装置', 'account_type' => 'asset'],
        ['code' => '1530', 'name' => '工具器具備品', 'account_type' => 'asset'],
        ['code' => '1590', 'name' => '建設仮勘定', 'account_type' => 'asset'],
        ['code' => '1600', 'name' => 'ソフトウェア', 'account_type' => 'asset'],
        ['code' => '1610', 'name' => 'ソフトウェア仮勘定', 'account_type' => 'asset'],

        ['code' => '2000', 'name' => '買掛金', 'account_type' => 'liability'],
        ['code' => '2100', 'name' => '未払金', 'account_type' => 'liability'],
        ['code' => '2200', 'name' => '未払費用', 'account_type' => 'liability'],
        ['code' => '2300', 'name' => '前受金', 'account_type' => 'liability'],
        ['code' => '2400', 'name' => '借入金', 'account_type' => 'liability'],

        ['code' => '3000', 'name' => '資本金', 'account_type' => 'equity'],
        ['code' => '3100', 'name' => '資本剰余金', 'account_type' => 'equity'],
        ['code' => '3200', 'name' => '利益剰余金', 'account_type' => 'equity'],

        ['code' => '4000', 'name' => '製品売上高', 'account_type' => 'revenue'],
        ['code' => '4100', 'name' => 'サービス売上高', 'account_type' => 'revenue'],
        ['code' => '4200', 'name' => '保守サービス売上高', 'account_type' => 'revenue'],
        ['code' => '4300', 'name' => 'ソフトウェア・ライセンス売上高', 'account_type' => 'revenue'],
        ['code' => '4900', 'name' => 'その他売上高', 'account_type' => 'revenue'],

        ['code' => '5000', 'name' => '製品売上原価', 'account_type' => 'expense'],
        ['code' => '5100', 'name' => 'サービス原価', 'account_type' => 'expense'],
        ['code' => '5200', 'name' => '材料費', 'account_type' => 'expense'],
        ['code' => '5300', 'name' => '外注加工費', 'account_type' => 'expense'],
        ['code' => '5400', 'name' => '保守サービス原価', 'account_type' => 'expense'],

        ['code' => '6000', 'name' => '給与手当', 'account_type' => 'expense'],
        ['code' => '6010', 'name' => '賞与', 'account_type' => 'expense'],
        ['code' => '6020', 'name' => '法定福利費', 'account_type' => 'expense'],
        ['code' => '6030', 'name' => '福利厚生費', 'account_type' => 'expense'],

        ['code' => '6100', 'name' => '外注費', 'account_type' => 'expense'],
        ['code' => '6110', 'name' => '業務委託費', 'account_type' => 'expense'],
        ['code' => '6120', 'name' => 'システム開発委託費', 'account_type' => 'expense'],
        ['code' => '6130', 'name' => '保守委託費', 'account_type' => 'expense'],
        ['code' => '6200', 'name' => 'クラウド利用料', 'account_type' => 'expense'],
        ['code' => '6210', 'name' => 'ソフトウェア利用料', 'account_type' => 'expense'],
        ['code' => '6220', 'name' => '通信費', 'account_type' => 'expense'],
        ['code' => '6230', 'name' => 'システム保守費', 'account_type' => 'expense'],

        ['code' => '6300', 'name' => '旅費交通費', 'account_type' => 'expense'],
        ['code' => '6310', 'name' => '出張費', 'account_type' => 'expense'],
        ['code' => '6320', 'name' => '会議費', 'account_type' => 'expense'],
        ['code' => '6330', 'name' => '交際費', 'account_type' => 'expense'],
        ['code' => '6400', 'name' => '消耗品費', 'account_type' => 'expense'],
        ['code' => '6410', 'name' => '事務用品費', 'account_type' => 'expense'],
        ['code' => '6420', 'name' => '印刷費', 'account_type' => 'expense'],
        ['code' => '6500', 'name' => '賃借料', 'account_type' => 'expense'],
        ['code' => '6510', 'name' => '水道光熱費', 'account_type' => 'expense'],
        ['code' => '6600', 'name' => '教育研修費', 'account_type' => 'expense'],
        ['code' => '6610', 'name' => '採用費', 'account_type' => 'expense'],
        ['code' => '6700', 'name' => '広告宣伝費', 'account_type' => 'expense'],
        ['code' => '6800', 'name' => '支払手数料', 'account_type' => 'expense'],
        ['code' => '6810', 'name' => '租税公課', 'account_type' => 'expense'],
        ['code' => '6900', 'name' => 'その他経費', 'account_type' => 'expense'],
        ['code' => '7000', 'name' => '減価償却費', 'account_type' => 'expense'],
        ['code' => '7100', 'name' => '研究開発費', 'account_type' => 'expense'],
        ['code' => '7200', 'name' => '修繕費', 'account_type' => 'expense'],
        ['code' => '7300', 'name' => '貸倒関連費用', 'account_type' => 'expense'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Organization::query()->doesntExist()) {
            $this->command?->warn('Organizationがありません。先に画面からユーザー登録してください。');

            return;
        }

        Organization::query()
            ->select('id')
            ->eachById(function (Organization $organization): void {
                $now = now();
                $managementAccounts = array_map(
                    fn (array $managementAccount): array => [
                        'organization_id' => $organization->id,
                        ...$managementAccount,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    self::ACCOUNTS
                );

                ManagementAccount::query()->upsert(
                    $managementAccounts,
                    ['organization_id', 'code'],
                    ['name', 'account_type', 'is_active', 'updated_at']
                );

                $this->removeObsoleteAccounts($organization);
            });
    }

    private function removeObsoleteAccounts(Organization $organization): void
    {
        $obsoleteAccounts = ManagementAccount::query()
            ->forOrganization($organization->id)
            ->whereIn('code', self::OBSOLETE_ACCOUNT_CODES)
            ->get();

        foreach ($obsoleteAccounts as $obsoleteAccount) {
            if ($obsoleteAccount->monthlyAmounts()->exists()) {
                $obsoleteAccount->update(['is_active' => false]);

                continue;
            }

            $obsoleteAccount->delete();
        }
    }
}
