<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExcelExportController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\JournalDocumentController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\LedgerAccountController;
use App\Http\Controllers\ManagementAccountController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\MonthlyAmountController;
use App\Models\FixedAsset;
use App\Models\MonthlyAmount;
use App\Services\ProfitAndLossSummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware([
    'auth',
    config('jetstream.auth_session'),
])->group(function () {
    Route::get('/dashboard', function (ProfitAndLossSummaryService $profitAndLossSummaryService) {
        $organization = request()->user()
            ->organization()
            ->with('company:id,name,fiscal_year_start_month')
            ->firstOrFail();

        $profitAndLossSummary = $profitAndLossSummaryService->summarizeCurrentFiscalYear(
            $organization->id,
            (int) $organization->company->fiscal_year_start_month,
        );
        $chartRevenue = max((float) $profitAndLossSummary['totalRevenue'], 0);
        $chartExpenses = max((float) $profitAndLossSummary['expensesBeforeTax'], 0);
        $chartMaximum = max($chartRevenue, $chartExpenses);
        $chartHeight = static function (float $amount) use ($chartMaximum): string {
            if ($amount <= 0 || $chartMaximum <= 0) {
                return '0.00';
            }

            return number_format(($amount / $chartMaximum) * 100, 2, '.', '');
        };
        $profitAndLossChart = [
            'revenue' => $profitAndLossSummary['totalRevenue'],
            'expenses' => $profitAndLossSummary['expensesBeforeTax'],
            'profitOrLoss' => $profitAndLossSummary['profitBeforeTax'],
            'revenueHeightPercentage' => $chartHeight($chartRevenue),
            'expenseHeightPercentage' => $chartHeight($chartExpenses),
        ];
        $period = [
            $profitAndLossSummary['periodStart'],
            $profitAndLossSummary['periodEnd'],
        ];

        $organization->loadCount([
            'monthlyAmounts as current_fiscal_year_budget_records_count' => fn (Builder $query): Builder => $query
                ->where('type', 'budget')
                ->whereBetween('period', $period),
            'monthlyAmounts as current_fiscal_year_actual_records_count' => fn (Builder $query): Builder => $query
                ->where('type', 'actual')
                ->whereBetween('period', $period),
            'departments as active_departments_count' => fn (Builder $query): Builder => $query->where('is_active', true),
            'managementAccounts as active_management_accounts_count' => fn (Builder $query): Builder => $query->where('is_active', true),
            'ledgerAccounts as active_ledger_accounts_count' => fn (Builder $query): Builder => $query->where('is_active', true),
            'journalEntries as current_fiscal_year_journal_entries_count' => fn (Builder $query): Builder => $query
                ->whereBetween('entry_date', $period),
        ]);

        $summary = FixedAsset::query()
            ->forOrganization($organization->id)
            ->where('status', 'held')
            ->toBase()
            ->selectRaw('COUNT(*) as asset_count')
            ->selectRaw('COALESCE(SUM(acquisition_cost), 0) as acquisition_cost')
            ->selectRaw('COALESCE(SUM(acquisition_cost - accumulated_depreciation), 0) as book_value')
            ->first();
        $currentMonth = CarbonImmutable::today()->startOfMonth();
        $monthlyTotals = MonthlyAmount::query()
            ->where('monthly_amounts.organization_id', $organization->id)
            ->where('monthly_amounts.period', $currentMonth->toDateString())
            ->join('management_accounts', 'management_accounts.id', '=', 'monthly_amounts.management_account_id')
            ->toBase()
            ->selectRaw("COALESCE(SUM(CASE WHEN monthly_amounts.type = 'budget' AND management_accounts.account_type = 'revenue' THEN monthly_amounts.amount ELSE 0 END), 0) AS budget_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN monthly_amounts.type = 'budget' AND management_accounts.account_type = 'expense' THEN monthly_amounts.amount ELSE 0 END), 0) AS budget_expenses")
            ->selectRaw("COALESCE(SUM(CASE WHEN monthly_amounts.type = 'actual' AND management_accounts.account_type = 'revenue' THEN monthly_amounts.amount ELSE 0 END), 0) AS actual_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN monthly_amounts.type = 'actual' AND management_accounts.account_type = 'expense' THEN monthly_amounts.amount ELSE 0 END), 0) AS actual_expenses")
            ->selectRaw("SUM(CASE WHEN monthly_amounts.type = 'budget' THEN 1 ELSE 0 END) AS budget_records")
            ->selectRaw("SUM(CASE WHEN monthly_amounts.type = 'actual' THEN 1 ELSE 0 END) AS actual_records")
            ->first();
        $budgetRevenue = (float) $monthlyTotals->budget_revenue;
        $budgetExpenses = (float) $monthlyTotals->budget_expenses;
        $actualRevenue = (float) $monthlyTotals->actual_revenue;
        $actualExpenses = (float) $monthlyTotals->actual_expenses;
        $budgetProfitOrLoss = $budgetRevenue - $budgetExpenses;
        $actualProfitOrLoss = $actualRevenue - $actualExpenses;
        $monthlyChartMaximum = max(abs($budgetProfitOrLoss), abs($actualProfitOrLoss));
        $monthlyChartHeight = static function (float $amount) use ($monthlyChartMaximum): string {
            if ($monthlyChartMaximum <= 0) {
                return '0.00';
            }

            return number_format((abs($amount) / $monthlyChartMaximum) * 100, 2, '.', '');
        };
        $budgetRecords = (int) $monthlyTotals->budget_records;
        $actualRecords = (int) $monthlyTotals->actual_records;
        $monthlyBudgetActualSummary = [
            'monthLabel' => $currentMonth->format('Y年n月'),
            'budget' => [
                'records' => $budgetRecords,
                'revenue' => number_format($budgetRevenue, 2, '.', ''),
                'expenses' => number_format($budgetExpenses, 2, '.', ''),
                'profitOrLoss' => number_format($budgetProfitOrLoss, 2, '.', ''),
                'heightPercentage' => $monthlyChartHeight($budgetProfitOrLoss),
            ],
            'actual' => [
                'records' => $actualRecords,
                'revenue' => number_format($actualRevenue, 2, '.', ''),
                'expenses' => number_format($actualExpenses, 2, '.', ''),
                'profitOrLoss' => number_format($actualProfitOrLoss, 2, '.', ''),
                'heightPercentage' => $monthlyChartHeight($actualProfitOrLoss),
            ],
            'variance' => $budgetRecords > 0 && $actualRecords > 0
                ? number_format($actualProfitOrLoss - $budgetProfitOrLoss, 2, '.', '')
                : null,
        ];

        return view('dashboard', [
            'organization' => $organization,
            'profitAndLossSummary' => $profitAndLossSummary,
            'profitAndLossChart' => $profitAndLossChart,
            'monthlyBudgetActualSummary' => $monthlyBudgetActualSummary,
            'fixedAssetSummary' => [
                'count' => (int) $summary->asset_count,
                'acquisitionCost' => number_format((float) $summary->acquisition_cost, 2, '.', ''),
                'bookValue' => number_format((float) $summary->book_value, 2, '.', ''),
            ],
        ]);
    })->name('dashboard');

    Route::get('/export/excel', ExcelExportController::class)
        ->name('export.excel');

    Route::get('/management', ManagementController::class)
        ->name('management.index');

    Route::resource('companies', CompanyController::class)
        ->except('show');

    Route::resource('departments', DepartmentController::class)
        ->except(['show', 'destroy']);
    Route::patch('departments/{department}/status', [DepartmentController::class, 'toggleStatus'])
        ->name('departments.status');

    Route::resource('management-accounts', ManagementAccountController::class)
        ->except(['show', 'destroy']);
    Route::patch('management-accounts/{management_account}/status', [ManagementAccountController::class, 'toggleStatus'])
        ->name('management-accounts.status');

    Route::resource('ledger-accounts', LedgerAccountController::class)
        ->except(['show', 'destroy']);
    Route::patch('ledger-accounts/{ledger_account}/status', [LedgerAccountController::class, 'updateStatus'])
        ->name('ledger-accounts.status');

    Route::resource('journal-entries', JournalEntryController::class);
    Route::post('journal-entries/{journal_entry}/documents', [JournalDocumentController::class, 'store'])
        ->name('journal-entries.documents.store');
    Route::get('journal-entries/{journal_entry}/documents/{journal_document}', [JournalDocumentController::class, 'show'])
        ->name('journal-entries.documents.show');
    Route::delete('journal-entries/{journal_entry}/documents/{journal_document}', [JournalDocumentController::class, 'destroy'])
        ->name('journal-entries.documents.destroy');

    Route::resource('amounts', MonthlyAmountController::class)
        ->except('show');

    Route::resource('fixed-assets', FixedAssetController::class);
});
