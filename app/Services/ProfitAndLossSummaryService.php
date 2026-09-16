<?php

namespace App\Services;

use App\Models\JournalEntryLine;
use Carbon\CarbonImmutable;

class ProfitAndLossSummaryService
{
    public function __construct(private FiscalYearService $fiscalYearService) {}

    /**
     * @return array{
     *     fiscalYear: int,
     *     periodStart: string,
     *     periodEnd: string,
     *     operatingRevenue: string,
     *     operatingExpenses: string,
     *     operatingProfit: string,
     *     nonOperatingRevenue: string,
     *     nonOperatingExpenses: string,
     *     ordinaryProfit: string,
     *     extraordinaryIncome: string,
     *     extraordinaryLoss: string,
     *     profitBeforeTax: string,
     *     totalRevenue: string,
     *     expensesBeforeTax: string,
     *     incomeTaxes: string,
     *     unclassifiedAccountsCount: int
     * }
     */
    public function summarizeCurrentFiscalYear(
        int $organizationId,
        int $fiscalYearStartMonth,
        ?CarbonImmutable $asOf = null,
    ): array {
        return $this->summarizeFiscalYear(
            $organizationId,
            $fiscalYearStartMonth,
            $this->fiscalYearService->current($fiscalYearStartMonth, $asOf),
        );
    }

    /**
     * @return array{
     *     fiscalYear: int,
     *     periodStart: string,
     *     periodEnd: string,
     *     operatingRevenue: string,
     *     operatingExpenses: string,
     *     operatingProfit: string,
     *     nonOperatingRevenue: string,
     *     nonOperatingExpenses: string,
     *     ordinaryProfit: string,
     *     extraordinaryIncome: string,
     *     extraordinaryLoss: string,
     *     profitBeforeTax: string,
     *     totalRevenue: string,
     *     expensesBeforeTax: string,
     *     incomeTaxes: string,
     *     unclassifiedAccountsCount: int
     * }
     */
    public function summarizeFiscalYear(
        int $organizationId,
        int $fiscalYearStartMonth,
        int $fiscalYear,
    ): array {
        $period = $this->fiscalYearService->period($fiscalYear, $fiscalYearStartMonth);
        $periodStart = $period['start'];
        $periodEnd = $period['end'];
        $sections = [
            'operatingRevenue' => 0.0,
            'operatingExpenses' => 0.0,
            'nonOperatingRevenue' => 0.0,
            'nonOperatingExpenses' => 0.0,
            'extraordinaryIncome' => 0.0,
            'extraordinaryLoss' => 0.0,
            'incomeTaxes' => 0.0,
        ];
        $unclassifiedAccountsCount = 0;

        $balances = JournalEntryLine::query()
            ->forOrganization($organizationId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_entry_lines.ledger_account_id')
            ->whereBetween('journal_entries.entry_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereIn('ledger_accounts.account_type', ['revenue', 'expense'])
            ->groupBy('ledger_accounts.code', 'ledger_accounts.account_type')
            ->select(['ledger_accounts.code', 'ledger_accounts.account_type'])
            ->selectRaw(
                "SUM(CASE
                    WHEN ledger_accounts.account_type = 'revenue'
                        THEN CASE WHEN journal_entry_lines.side = 'credit' THEN journal_entry_lines.amount ELSE -journal_entry_lines.amount END
                    ELSE CASE WHEN journal_entry_lines.side = 'debit' THEN journal_entry_lines.amount ELSE -journal_entry_lines.amount END
                END) AS balance"
            )
            ->toBase()
            ->get();

        foreach ($balances as $balance) {
            $section = $this->sectionFor((string) $balance->account_type, (string) $balance->code);

            if ($section === null) {
                $unclassifiedAccountsCount++;

                continue;
            }

            $sections[$section] += (float) $balance->balance;
        }

        $operatingProfit = $sections['operatingRevenue'] - $sections['operatingExpenses'];
        $ordinaryProfit = $operatingProfit
            + $sections['nonOperatingRevenue']
            - $sections['nonOperatingExpenses'];
        $profitBeforeTax = $ordinaryProfit
            + $sections['extraordinaryIncome']
            - $sections['extraordinaryLoss'];
        $totalRevenue = $sections['operatingRevenue']
            + $sections['nonOperatingRevenue']
            + $sections['extraordinaryIncome'];
        $expensesBeforeTax = $sections['operatingExpenses']
            + $sections['nonOperatingExpenses']
            + $sections['extraordinaryLoss'];

        return [
            'fiscalYear' => $fiscalYear,
            'periodStart' => $periodStart->toDateString(),
            'periodEnd' => $periodEnd->toDateString(),
            'operatingRevenue' => $this->decimal($sections['operatingRevenue']),
            'operatingExpenses' => $this->decimal($sections['operatingExpenses']),
            'operatingProfit' => $this->decimal($operatingProfit),
            'nonOperatingRevenue' => $this->decimal($sections['nonOperatingRevenue']),
            'nonOperatingExpenses' => $this->decimal($sections['nonOperatingExpenses']),
            'ordinaryProfit' => $this->decimal($ordinaryProfit),
            'extraordinaryIncome' => $this->decimal($sections['extraordinaryIncome']),
            'extraordinaryLoss' => $this->decimal($sections['extraordinaryLoss']),
            'profitBeforeTax' => $this->decimal($profitBeforeTax),
            'totalRevenue' => $this->decimal($totalRevenue),
            'expensesBeforeTax' => $this->decimal($expensesBeforeTax),
            'incomeTaxes' => $this->decimal($sections['incomeTaxes']),
            'unclassifiedAccountsCount' => $unclassifiedAccountsCount,
        ];
    }

    private function sectionFor(string $accountType, string $code): ?string
    {
        if (! ctype_digit($code)) {
            return null;
        }

        $numericCode = (int) $code;

        return match ($accountType) {
            'revenue' => match (true) {
                $numericCode >= 4000 && $numericCode <= 4199 => 'operatingRevenue',
                $numericCode >= 4200 && $numericCode <= 4299 => 'nonOperatingRevenue',
                $numericCode >= 4300 && $numericCode <= 4399 => 'extraordinaryIncome',
                default => null,
            },
            'expense' => match (true) {
                $numericCode >= 5000 && $numericCode <= 7299 => 'operatingExpenses',
                $numericCode >= 7300 && $numericCode <= 7499 => 'nonOperatingExpenses',
                $numericCode >= 7500 && $numericCode <= 7599 => 'extraordinaryLoss',
                $numericCode >= 7600 && $numericCode <= 7699 => 'incomeTaxes',
                default => null,
            },
            default => null,
        };
    }

    private function decimal(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
