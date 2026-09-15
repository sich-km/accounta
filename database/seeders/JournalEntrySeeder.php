<?php

namespace Database\Seeders;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JournalEntrySeeder extends Seeder
{
    private const int YEAR = 2026;

    private const string SAMPLE_NOTE = '小規模SaaS・IT支援・小売業の月次サンプル仕訳';

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
            'notes' => self::SAMPLE_NOTE,
            'lines' => [
                $this->line('debit', '1030', 1000000, null, '普通預金へ入金'),
                $this->line('credit', '3000', 1000000, null, '設立時資本金'),
            ],
        ]];

        foreach (self::MONTHLY_AMOUNTS as $monthlyAmounts) {
            array_push($entries, ...$this->monthlyEntries($monthlyAmounts));
        }

        return $entries;
    }

    /**
     * @param  array{month: int, saas: int, advertising_revenue: int, development: int, maintenance: int, retail_sales: int, purchases: int, cloud: int, rent: int, marketing: int, payment_fees: int, consumables: int, outsourcing: int}  $amounts
     * @return list<array{entry_date: string, originating_department: ?string, description: string, notes: string, lines: list<array{side: string, code: string, amount: string, department: ?string, description: string}>}>
     */
    private function monthlyEntries(array $amounts): array
    {
        $month = $amounts['month'];
        $monthLabel = "{$month}月";
        $period = CarbonImmutable::create(self::YEAR, $month, 1);
        $date = fn (int $day): string => sprintf('%d-%02d-%02d', self::YEAR, $month, $day);
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
                'notes' => self::SAMPLE_NOTE,
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
                'notes' => self::SAMPLE_NOTE,
                'lines' => [
                    $this->line('debit', '5100', $amounts['purchases'], 'D410', "{$monthLabel}商品A仕入"),
                    $this->line('credit', '2000', $amounts['purchases'], null, '仕入先への買掛金計上'),
                ],
            ],
            [
                'entry_date' => $date(15),
                'originating_department' => 'D410',
                'description' => "{$monthLabel}商品A売上",
                'notes' => self::SAMPLE_NOTE,
                'lines' => [
                    $this->line('debit', '1030', $amounts['retail_sales'], null, '商品A販売代金の入金'),
                    $this->line('credit', '4000', $amounts['retail_sales'], 'D410', "{$monthLabel}商品A売上"),
                ],
            ],
            [
                'entry_date' => $date(20),
                'originating_department' => 'D310',
                'description' => "{$monthLabel}IT開発・保守売上",
                'notes' => self::SAMPLE_NOTE,
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
                'notes' => self::SAMPLE_NOTE,
                'lines' => [
                    $this->line('debit', '2000', $amounts['purchases'], null, '買掛金の決済'),
                    $this->line('credit', '1030', $amounts['purchases'], null, '普通預金から支払'),
                ],
            ],
            [
                'entry_date' => $date(25),
                'originating_department' => 'D120',
                'description' => "{$monthLabel}人件費",
                'notes' => self::SAMPLE_NOTE,
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
                'notes' => self::SAMPLE_NOTE,
                'lines' => [
                    $this->line('debit', '1030', $itRevenueTotal, null, '普通預金へ入金'),
                    $this->line('credit', '1100', $itRevenueTotal, null, 'IT開発・保守売掛金の回収'),
                ],
            ],
            [
                'entry_date' => $period->endOfMonth()->toDateString(),
                'originating_department' => 'D110',
                'description' => "{$monthLabel}営業費用",
                'notes' => self::SAMPLE_NOTE,
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
                    'notes' => self::SAMPLE_NOTE,
                ]);
        }
    }
}
