<?php

namespace App\Exports;

use App\Models\BudgetActualEntry;
use App\Models\JournalEntryLine;
use App\Models\Organization;
use App\Services\FiscalYearService;
use App\Services\ProfitAndLossSummaryService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheetExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    public function __construct(
        private readonly int $organizationId,
        private readonly FiscalYearService $fiscalYearService,
        private readonly ProfitAndLossSummaryService $profitAndLossSummaryService,
    ) {}

    /**
     * @return array<int, array<int, float|int|string|null>>
     */
    public function array(): array
    {
        $organization = Organization::query()
            ->with('company:id,fiscal_year_start_month')
            ->findOrFail($this->organizationId);
        $fiscalYearStartMonth = (int) $organization->company->fiscal_year_start_month;
        $fiscalYear = $this->fiscalYearService->current($fiscalYearStartMonth);
        $period = $this->fiscalYearService->period($fiscalYear, $fiscalYearStartMonth);
        $profitAndLoss = $this->profitAndLossSummaryService->summarizeFiscalYear(
            $this->organizationId,
            $fiscalYearStartMonth,
            $fiscalYear,
        );
        $balanceSheet = $this->balanceSheetSummary($period['end']->toDateString());
        $budgetActual = $this->budgetActualSummary(
            $period['start']->toDateString(),
            $period['end']->toDateString(),
        );

        return [
            ['Accounta まとめ'],
            ['対象年度', $fiscalYear.'年度'],
            ['会計期間', $period['start']->toDateString(), $period['end']->toDateString()],
            [],
            ['P/Lまとめ'],
            ['項目', '金額'],
            ['収益合計', (float) $profitAndLoss['totalRevenue']],
            ['費用合計（税引前）', (float) $profitAndLoss['expensesBeforeTax']],
            ['営業利益', (float) $profitAndLoss['operatingProfit']],
            ['経常利益', (float) $profitAndLoss['ordinaryProfit']],
            ['税引前当期純利益', (float) $profitAndLoss['profitBeforeTax']],
            [],
            ['B/Sまとめ'],
            ['項目', '金額'],
            ['資産', $balanceSheet['assets']],
            ['負債', $balanceSheet['liabilities']],
            ['純資産', $balanceSheet['equity']],
            ['負債・純資産合計', $balanceSheet['liabilitiesAndEquity']],
            ['貸借差額', $balanceSheet['difference']],
            ['※簡易集計：資産・負債・純資産勘定の期末残高です。決算振替前は貸借差額が発生します。'],
            [],
            ['予実まとめ'],
            ['項目', '予算', '実績', '差異（実績－予算）'],
            ['収益', $budgetActual['budgetRevenue'], $budgetActual['actualRevenue'], $budgetActual['actualRevenue'] - $budgetActual['budgetRevenue']],
            ['費用', $budgetActual['budgetExpenses'], $budgetActual['actualExpenses'], $budgetActual['actualExpenses'] - $budgetActual['budgetExpenses']],
            ['損益', $budgetActual['budgetProfitOrLoss'], $budgetActual['actualProfitOrLoss'], $budgetActual['actualProfitOrLoss'] - $budgetActual['budgetProfitOrLoss']],
            ['レコード数', $budgetActual['budgetRecords'], $budgetActual['actualRecords']],
        ];
    }

    public function title(): string
    {
        return 'まとめ';
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    /** @return array<string, float|int> */
    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 20,
            'C' => 20,
            'D' => 22,
        ];
    }

    /** @return array<int|string, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            5 => ['font' => ['bold' => true, 'size' => 13]],
            6 => ['font' => ['bold' => true]],
            13 => ['font' => ['bold' => true, 'size' => 13]],
            14 => ['font' => ['bold' => true]],
            22 => ['font' => ['bold' => true, 'size' => 13]],
            23 => ['font' => ['bold' => true]],
            'B27:C27' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER]],
        ];
    }

    /**
     * @return array{assets: float, liabilities: float, equity: float, liabilitiesAndEquity: float, difference: float}
     */
    private function balanceSheetSummary(string $periodEnd): array
    {
        $balances = JournalEntryLine::query()
            ->forOrganization($this->organizationId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_entry_lines.ledger_account_id')
            ->whereDate('journal_entries.entry_date', '<=', $periodEnd)
            ->whereIn('ledger_accounts.account_type', ['asset', 'liability', 'equity'])
            ->groupBy('ledger_accounts.account_type')
            ->select('ledger_accounts.account_type')
            ->selectRaw(
                "SUM(CASE
                    WHEN ledger_accounts.account_type = 'asset'
                        THEN CASE WHEN journal_entry_lines.side = 'debit' THEN journal_entry_lines.amount ELSE -journal_entry_lines.amount END
                    ELSE CASE WHEN journal_entry_lines.side = 'credit' THEN journal_entry_lines.amount ELSE -journal_entry_lines.amount END
                END) AS balance"
            )
            ->toBase()
            ->pluck('balance', 'account_type');

        $assets = (float) ($balances['asset'] ?? 0);
        $liabilities = (float) ($balances['liability'] ?? 0);
        $equity = (float) ($balances['equity'] ?? 0);
        $liabilitiesAndEquity = $liabilities + $equity;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'liabilitiesAndEquity' => $liabilitiesAndEquity,
            'difference' => $assets - $liabilitiesAndEquity,
        ];
    }

    /**
     * @return array{
     *     budgetRevenue: float,
     *     budgetExpenses: float,
     *     budgetProfitOrLoss: float,
     *     budgetRecords: int,
     *     actualRevenue: float,
     *     actualExpenses: float,
     *     actualProfitOrLoss: float,
     *     actualRecords: int
     * }
     */
    private function budgetActualSummary(string $periodStart, string $periodEnd): array
    {
        $totals = BudgetActualEntry::query()
            ->forOrganization($this->organizationId)
            ->whereBetween('budget_actual_entries.period', [$periodStart, $periodEnd])
            ->join('budget_actual_accounts', 'budget_actual_accounts.id', '=', 'budget_actual_entries.budget_actual_account_id')
            ->toBase()
            ->selectRaw("COALESCE(SUM(CASE WHEN budget_actual_entries.type = 'budget' AND budget_actual_accounts.account_type = 'revenue' THEN budget_actual_entries.amount ELSE 0 END), 0) AS budget_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN budget_actual_entries.type = 'budget' AND budget_actual_accounts.account_type = 'expense' THEN budget_actual_entries.amount ELSE 0 END), 0) AS budget_expenses")
            ->selectRaw("COALESCE(SUM(CASE WHEN budget_actual_entries.type = 'actual' AND budget_actual_accounts.account_type = 'revenue' THEN budget_actual_entries.amount ELSE 0 END), 0) AS actual_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN budget_actual_entries.type = 'actual' AND budget_actual_accounts.account_type = 'expense' THEN budget_actual_entries.amount ELSE 0 END), 0) AS actual_expenses")
            ->selectRaw("SUM(CASE WHEN budget_actual_entries.type = 'budget' THEN 1 ELSE 0 END) AS budget_records")
            ->selectRaw("SUM(CASE WHEN budget_actual_entries.type = 'actual' THEN 1 ELSE 0 END) AS actual_records")
            ->first();

        $budgetRevenue = (float) $totals->budget_revenue;
        $budgetExpenses = (float) $totals->budget_expenses;
        $actualRevenue = (float) $totals->actual_revenue;
        $actualExpenses = (float) $totals->actual_expenses;

        return [
            'budgetRevenue' => $budgetRevenue,
            'budgetExpenses' => $budgetExpenses,
            'budgetProfitOrLoss' => $budgetRevenue - $budgetExpenses,
            'budgetRecords' => (int) $totals->budget_records,
            'actualRevenue' => $actualRevenue,
            'actualExpenses' => $actualExpenses,
            'actualProfitOrLoss' => $actualRevenue - $actualExpenses,
            'actualRecords' => (int) $totals->actual_records,
        ];
    }
}
