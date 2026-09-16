<?php

namespace Database\Seeders;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JournalEntrySeeder extends Seeder
{
    private const int YEAR = 2026;

    private const int PRIOR_YEAR = 2025;

    /**
     * @var list<array{month: int, saas: int, advertising_revenue: int, development: int, maintenance: int, retail_sales: int, purchases: int, cloud: int, rent: int, marketing: int, payment_fees: int, consumables: int, outsourcing: int}>
     */
    private const array PRIOR_YEAR_MONTHLY_AMOUNTS = [
        ['month' => 1, 'saas' => 250000, 'advertising_revenue' => 40000, 'development' => 300000, 'maintenance' => 90000, 'retail_sales' => 220000, 'purchases' => 132000, 'cloud' => 55000, 'rent' => 60000, 'marketing' => 25000, 'payment_fees' => 12000, 'consumables' => 16000, 'outsourcing' => 70000],
        ['month' => 2, 'saas' => 255000, 'advertising_revenue' => 40000, 'development' => 280000, 'maintenance' => 90000, 'retail_sales' => 210000, 'purchases' => 126000, 'cloud' => 56000, 'rent' => 60000, 'marketing' => 24000, 'payment_fees' => 12000, 'consumables' => 16000, 'outsourcing' => 81000],
        ['month' => 3, 'saas' => 260000, 'advertising_revenue' => 42000, 'development' => 310000, 'maintenance' => 92000, 'retail_sales' => 225000, 'purchases' => 135000, 'cloud' => 57000, 'rent' => 60000, 'marketing' => 25000, 'payment_fees' => 13000, 'consumables' => 16000, 'outsourcing' => 98000],
        ['month' => 4, 'saas' => 265000, 'advertising_revenue' => 42000, 'development' => 320000, 'maintenance' => 94000, 'retail_sales' => 230000, 'purchases' => 138000, 'cloud' => 58000, 'rent' => 60000, 'marketing' => 26000, 'payment_fees' => 13000, 'consumables' => 17000, 'outsourcing' => 104000],
        ['month' => 5, 'saas' => 270000, 'advertising_revenue' => 43000, 'development' => 290000, 'maintenance' => 95000, 'retail_sales' => 220000, 'purchases' => 132000, 'cloud' => 59000, 'rent' => 60000, 'marketing' => 27000, 'payment_fees' => 13000, 'consumables' => 17000, 'outsourcing' => 100000],
        ['month' => 6, 'saas' => 275000, 'advertising_revenue' => 44000, 'development' => 330000, 'maintenance' => 96000, 'retail_sales' => 235000, 'purchases' => 141000, 'cloud' => 60000, 'rent' => 60000, 'marketing' => 28000, 'payment_fees' => 14000, 'consumables' => 17000, 'outsourcing' => 120000],
        ['month' => 7, 'saas' => 280000, 'advertising_revenue' => 45000, 'development' => 300000, 'maintenance' => 98000, 'retail_sales' => 230000, 'purchases' => 138000, 'cloud' => 61000, 'rent' => 60000, 'marketing' => 28000, 'payment_fees' => 14000, 'consumables' => 18000, 'outsourcing' => 114000],
        ['month' => 8, 'saas' => 285000, 'advertising_revenue' => 46000, 'development' => 340000, 'maintenance' => 100000, 'retail_sales' => 240000, 'purchases' => 144000, 'cloud' => 62000, 'rent' => 60000, 'marketing' => 29000, 'payment_fees' => 14000, 'consumables' => 18000, 'outsourcing' => 154000],
        ['month' => 9, 'saas' => 290000, 'advertising_revenue' => 47000, 'development' => 350000, 'maintenance' => 102000, 'retail_sales' => 245000, 'purchases' => 147000, 'cloud' => 63000, 'rent' => 60000, 'marketing' => 30000, 'payment_fees' => 15000, 'consumables' => 18000, 'outsourcing' => 166000],
        ['month' => 10, 'saas' => 295000, 'advertising_revenue' => 48000, 'development' => 320000, 'maintenance' => 104000, 'retail_sales' => 240000, 'purchases' => 144000, 'cloud' => 64000, 'rent' => 60000, 'marketing' => 30000, 'payment_fees' => 15000, 'consumables' => 19000, 'outsourcing' => 160000],
        ['month' => 11, 'saas' => 300000, 'advertising_revenue' => 50000, 'development' => 360000, 'maintenance' => 105000, 'retail_sales' => 250000, 'purchases' => 150000, 'cloud' => 65000, 'rent' => 60000, 'marketing' => 31000, 'payment_fees' => 16000, 'consumables' => 19000, 'outsourcing' => 179000],
        ['month' => 12, 'saas' => 310000, 'advertising_revenue' => 52000, 'development' => 390000, 'maintenance' => 108000, 'retail_sales' => 270000, 'purchases' => 162000, 'cloud' => 68000, 'rent' => 60000, 'marketing' => 35000, 'payment_fees' => 17000, 'consumables' => 20000, 'outsourcing' => 213000],
    ];

    /**
     * @var list<array{month: int, saas: int, advertising_revenue: int, development: int, maintenance: int, retail_sales: int, purchases: int, cloud: int, rent: int, marketing: int, payment_fees: int, consumables: int, outsourcing: int}>
     */
    private const array MONTHLY_AMOUNTS = [
        ['month' => 1, 'saas' => 340000, 'advertising_revenue' => 60000, 'development' => 380000, 'maintenance' => 120000, 'retail_sales' => 300000, 'purchases' => 180000, 'cloud' => 70000, 'rent' => 60000, 'marketing' => 30000, 'payment_fees' => 15000, 'consumables' => 20000, 'outsourcing' => 0],
        ['month' => 2, 'saas' => 360000, 'advertising_revenue' => 65000, 'development' => 360000, 'maintenance' => 130000, 'retail_sales' => 320000, 'purchases' => 192000, 'cloud' => 72000, 'rent' => 60000, 'marketing' => 32000, 'payment_fees' => 16000, 'consumables' => 20000, 'outsourcing' => 0],
        ['month' => 3, 'saas' => 380000, 'advertising_revenue' => 70000, 'development' => 420000, 'maintenance' => 140000, 'retail_sales' => 340000, 'purchases' => 204000, 'cloud' => 75000, 'rent' => 60000, 'marketing' => 35000, 'payment_fees' => 18000, 'consumables' => 25000, 'outsourcing' => 100000],
        ['month' => 4, 'saas' => 400000, 'advertising_revenue' => 75000, 'development' => 390000, 'maintenance' => 150000, 'retail_sales' => 360000, 'purchases' => 216000, 'cloud' => 78000, 'rent' => 60000, 'marketing' => 38000, 'payment_fees' => 19000, 'consumables' => 25000, 'outsourcing' => 100000],
        ['month' => 5, 'saas' => 420000, 'advertising_revenue' => 80000, 'development' => 410000, 'maintenance' => 160000, 'retail_sales' => 380000, 'purchases' => 228000, 'cloud' => 82000, 'rent' => 60000, 'marketing' => 40000, 'payment_fees' => 20000, 'consumables' => 25000, 'outsourcing' => 150000],
        ['month' => 6, 'saas' => 450000, 'advertising_revenue' => 85000, 'development' => 430000, 'maintenance' => 170000, 'retail_sales' => 400000, 'purchases' => 240000, 'cloud' => 85000, 'rent' => 60000, 'marketing' => 45000, 'payment_fees' => 22000, 'consumables' => 28000, 'outsourcing' => 200000],
    ];

    public function run(): void
    {
        $entries = $this->entries();

        Organization::query()->select('id')->eachById(function (Organization $organization) use ($entries): void {
            DB::transaction(function () use ($organization, $entries): void {
                $accounts = $organization->ledgerAccounts()->pluck('id', 'code');
                $departments = $organization->departments()->pluck('id', 'code');

                $this->migrateLegacyEntries($organization);

                foreach ($entries as $entryData) {
                    $journalEntry = $organization->journalEntries()->updateOrCreate(
                        [
                            'entry_date' => $entryData['entry_date'],
                            'description' => $entryData['description'],
                        ],
                        [
                            'originating_department_id' => $entryData['originating_department'] === null
                                ? null
                                : ($departments[$entryData['originating_department']] ?? null),
                            'notes' => $entryData['notes'],
                        ],
                    );
                    $journalEntry->lines()->delete();

                    foreach ($entryData['lines'] as $index => $line) {
                        $journalEntry->lines()->create([
                            'organization_id' => $organization->id,
                            'line_number' => $index + 1,
                            'ledger_account_id' => $accounts[$line['code']],
                            'department_id' => $line['department'] === null ? null : ($departments[$line['department']] ?? null),
                            'side' => $line['side'],
                            'amount' => $line['amount'],
                            'description' => $line['description'],
                        ]);
                    }
                }
            });
        });
    }

    /**
     * @return list<array{entry_date: string, originating_department: ?string, description: string, notes: string, lines: list<array{side: string, code: string, amount: string, department: ?string, description: string}>}>
     */
    private function entries(): array
    {
        $entries = [[
            'entry_date' => self::YEAR.'-01-01',
            'originating_department' => null,
            'description' => '設立時資本金の入金',
            'notes' => '会社設立時の運転資金として、創業者から資本金1,000,000円の払込みを受けた。',
            'lines' => [
                $this->line('debit', '1030', 1000000, null, '普通預金へ入金'),
                $this->line('credit', '3000', 1000000, null, '設立時資本金'),
            ],
        ]];

        foreach (self::PRIOR_YEAR_MONTHLY_AMOUNTS as $monthlyAmounts) {
            array_push($entries, ...$this->monthlyEntries($monthlyAmounts, self::PRIOR_YEAR, true));
        }

        foreach (self::MONTHLY_AMOUNTS as $monthlyAmounts) {
            array_push($entries, ...$this->monthlyEntries($monthlyAmounts, self::YEAR));
        }

        array_push($entries, ...$this->extraordinaryEntries());

        return $entries;
    }

    /**
     * @return list<array{entry_date: string, originating_department: ?string, description: string, notes: string, lines: list<array{side: string, code: string, amount: string, department: ?string, description: string}>}>
     */
    private function extraordinaryEntries(): array
    {
        return [
            [
                'entry_date' => self::YEAR.'-06-18',
                'originating_department' => 'D130',
                'description' => '旧ネットワーク機器の売却',
                'notes' => '更新により不要となった旧ネットワーク機器を帳簿価額80,000円に対して100,000円で売却し、固定資産売却益20,000円を計上。',
                'lines' => [
                    $this->line('debit', '1030', 100000, null, '売却代金を普通預金へ入金'),
                    $this->line('debit', '1531', 120000, 'D130', '売却時点の減価償却累計額を取り崩し'),
                    $this->line('credit', '1530', 200000, 'D130', '旧ネットワーク機器の取得価額を除却'),
                    $this->line('credit', '4300', 20000, 'D130', '帳簿価額を上回る売却益'),
                ],
            ],
            [
                'entry_date' => self::YEAR.'-06-28',
                'originating_department' => 'D110',
                'description' => '投資有価証券の減損処理',
                'notes' => '保有する投資有価証券について実質価額が著しく下落したため、減損による評価損30,000円を特別損失として計上。',
                'lines' => [
                    $this->line('debit', '7500', 30000, 'D110', '減損による投資有価証券評価損'),
                    $this->line('credit', '1710', 30000, null, '投資有価証券の帳簿価額を減額'),
                ],
            ],
        ];
    }

    /**
     * @param  array{month: int, saas: int, advertising_revenue: int, development: int, maintenance: int, retail_sales: int, purchases: int, cloud: int, rent: int, marketing: int, payment_fees: int, consumables: int, outsourcing: int}  $amounts
     * @return list<array{entry_date: string, originating_department: ?string, description: string, notes: string, lines: list<array{side: string, code: string, amount: string, department: ?string, description: string}>}>
     */
    private function monthlyEntries(array $amounts, int $year, bool $includeYearInLabel = false): array
    {
        $month = $amounts['month'];
        $monthLabel = $includeYearInLabel ? "{$year}年{$month}月" : "{$month}月";
        $period = CarbonImmutable::create($year, $month, 1);
        $date = fn (int $day): string => sprintf('%d-%02d-%02d', $year, $month, $day);
        $saasAndAdvertisingTotal = $amounts['saas'] + $amounts['advertising_revenue'];
        $itRevenueTotal = $amounts['development'] + $amounts['maintenance'];
        $personnelTotal = 200000 + 250000 + 70000;
        $operatingExpenseTotal = $amounts['cloud']
            + $amounts['rent']
            + $amounts['marketing']
            + $amounts['payment_fees']
            + $amounts['consumables']
            + $amounts['outsourcing'];
        $operatingExpenseLines = [
            $this->line('debit', '6160', $amounts['cloud'], 'D310', "{$monthLabel}クラウド・通信費"),
            $this->line('debit', '6170', $amounts['rent'], 'D100', "{$monthLabel}事務所家賃"),
            $this->line('debit', '6150', $amounts['marketing'], 'D410', "{$monthLabel}広告宣伝費"),
            $this->line('debit', '7200', $amounts['payment_fees'], 'D110', "{$monthLabel}決済・振込手数料"),
            $this->line('debit', '6180', $amounts['consumables'], 'D100', "{$monthLabel}消耗品購入"),
        ];

        if ($amounts['outsourcing'] > 0) {
            $operatingExpenseLines[] = $this->line('debit', '6190', $amounts['outsourcing'], 'D310', "{$monthLabel}開発外注費");
        }

        $operatingExpenseLines[] = $this->line('credit', '1030', $operatingExpenseTotal, null, '普通預金から支払');

        return [
            [
                'entry_date' => $date(5),
                'originating_department' => 'D310',
                'description' => "{$monthLabel}SaaS・広告収入",
                'notes' => "{$monthLabel}分のSaaS利用料と広告収入について、プラットフォームから普通預金への入金を計上。",
                'lines' => [
                    $this->line('debit', '1030', $saasAndAdvertisingTotal, null, 'プラットフォームから普通預金へ入金'),
                    $this->line('credit', '4100', $amounts['saas'], 'D310', "{$monthLabel}SaaSサブスクリプション売上"),
                    $this->line('credit', '4190', $amounts['advertising_revenue'], 'D310', "{$monthLabel}広告収入"),
                ],
            ],
            [
                'entry_date' => $date(10),
                'originating_department' => 'D410',
                'description' => "{$monthLabel}商品A仕入",
                'notes' => "{$monthLabel}販売分の商品Aを仕入れ、代金を後日支払う買掛金として計上。",
                'lines' => [
                    $this->line('debit', '5100', $amounts['purchases'], 'D410', "{$monthLabel}商品A仕入"),
                    $this->line('credit', '2000', $amounts['purchases'], null, '仕入先への買掛金計上'),
                ],
            ],
            [
                'entry_date' => $date(15),
                'originating_department' => 'D410',
                'description' => "{$monthLabel}商品A売上",
                'notes' => "{$monthLabel}の商品A販売代金について、普通預金への入金と売上を計上。",
                'lines' => [
                    $this->line('debit', '1030', $amounts['retail_sales'], null, '商品A販売代金の入金'),
                    $this->line('credit', '4000', $amounts['retail_sales'], 'D410', "{$monthLabel}商品A売上"),
                ],
            ],
            [
                'entry_date' => $date(20),
                'originating_department' => 'D310',
                'description' => "{$monthLabel}IT開発・保守売上",
                'notes' => "{$monthLabel}に提供した受託システム開発と保守サービスの請求額を、売掛金として計上。",
                'lines' => [
                    $this->line('debit', '1100', $itRevenueTotal, null, '顧客への請求額'),
                    $this->line('credit', '4110', $amounts['development'], 'D310', "{$monthLabel}受託システム開発売上"),
                    $this->line('credit', '4100', $amounts['maintenance'], 'D210', "{$monthLabel}保守サービス売上"),
                ],
            ],
            [
                'entry_date' => $date(22),
                'originating_department' => 'D110',
                'description' => "{$monthLabel}商品A仕入代金の支払",
                'notes' => "{$monthLabel}の商品A仕入時に計上した買掛金を、普通預金から支払って決済。",
                'lines' => [
                    $this->line('debit', '2000', $amounts['purchases'], null, '買掛金の決済'),
                    $this->line('credit', '1030', $amounts['purchases'], null, '普通預金から支払'),
                ],
            ],
            [
                'entry_date' => $date(25),
                'originating_department' => 'D120',
                'description' => "{$monthLabel}人件費",
                'notes' => "{$monthLabel}分の従業員給与、創業者の役員報酬および法定福利費を普通預金から支払。",
                'lines' => [
                    $this->line('debit', '6000', 200000, 'D120', "{$monthLabel}従業員給与（1名）"),
                    $this->line('debit', '6060', 250000, 'D100', "{$monthLabel}創業者役員報酬"),
                    $this->line('debit', '6020', 70000, 'D120', "{$monthLabel}法定福利費"),
                    $this->line('credit', '1030', $personnelTotal, null, '普通預金から支払'),
                ],
            ],
            [
                'entry_date' => $date(27),
                'originating_department' => 'D110',
                'description' => "{$monthLabel}IT売掛金の回収",
                'notes' => "{$monthLabel}に請求した受託システム開発・保守サービスの売掛金を、普通預金で回収。",
                'lines' => [
                    $this->line('debit', '1030', $itRevenueTotal, null, '普通預金へ入金'),
                    $this->line('credit', '1100', $itRevenueTotal, null, 'IT開発・保守売掛金の回収'),
                ],
            ],
            [
                'entry_date' => $period->endOfMonth()->toDateString(),
                'originating_department' => 'D110',
                'description' => "{$monthLabel}営業費用",
                'notes' => "{$monthLabel}分のクラウド利用料、事務所家賃、広告宣伝費、決済・振込手数料、消耗品費"
                    .($amounts['outsourcing'] > 0 ? 'および開発外注費' : '')
                    .'を普通預金から支払。',
                'lines' => $operatingExpenseLines,
            ],
        ];
    }

    /**
     * @return array{side: string, code: string, amount: string, department: ?string, description: string}
     */
    private function line(string $side, string $code, int $amount, ?string $department, string $description): array
    {
        return [
            'side' => $side,
            'code' => $code,
            'amount' => number_format($amount, 2, '.', ''),
            'department' => $department,
            'description' => $description,
        ];
    }

    private function migrateLegacyEntries(Organization $organization): void
    {
        $legacyEntries = [
            ['entry_date' => '2026-04-01', 'description' => '設立時資本金の入金', 'notes' => '動作確認用の初期仕訳', 'new_entry_date' => '2026-01-01', 'new_description' => '設立時資本金の入金'],
            ['entry_date' => '2026-04-15', 'description' => 'サービス売上の入金', 'notes' => '動作確認用の初期仕訳', 'new_entry_date' => '2026-04-05', 'new_description' => '4月SaaS・広告収入'],
            ['entry_date' => '2026-04-25', 'description' => '給与・法定福利費の支払', 'notes' => '複合仕訳の動作確認用データ', 'new_entry_date' => '2026-04-25', 'new_description' => '4月人件費'],
        ];

        foreach ($legacyEntries as $legacyEntry) {
            $organization->journalEntries()
                ->whereDate('entry_date', $legacyEntry['entry_date'])
                ->where('description', $legacyEntry['description'])
                ->where('notes', $legacyEntry['notes'])
                ->update([
                    'entry_date' => $legacyEntry['new_entry_date'],
                    'description' => $legacyEntry['new_description'],
                ]);
        }
    }
}
