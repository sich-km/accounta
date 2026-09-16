<?php

namespace App\Livewire\Dashboard;

use App\Models\BudgetActualEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\DashboardFiscalYearPreferenceService;
use App\Services\FiscalYearService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BudgetActualSummary extends Component
{
    private const string PREFERENCE_SECTION = 'budget-actual';

    public int $fiscalYear;

    public function mount(
        FiscalYearService $fiscalYearService,
        DashboardFiscalYearPreferenceService $preferenceService,
    ): void {
        $user = $this->user();
        $organization = $this->organization($user);
        $currentFiscalYear = $fiscalYearService->current($this->fiscalYearStartMonth($organization));
        $this->fiscalYear = $preferenceService->getFiscalYear(
            $user,
            self::PREFERENCE_SECTION,
            $currentFiscalYear,
        );
    }

    public function render(
        FiscalYearService $fiscalYearService,
        DashboardFiscalYearPreferenceService $preferenceService,
    ): View {
        $user = $this->user();
        $organization = $this->organization($user);
        $fiscalYearStartMonth = $this->fiscalYearStartMonth($organization);
        $availableFiscalYears = $fiscalYearService->availableYears(
            BudgetActualEntry::query()
                ->forOrganization($organization->id)
                ->distinct()
                ->pluck('period'),
            $fiscalYearStartMonth,
        );
        $currentFiscalYear = $fiscalYearService->current($fiscalYearStartMonth);

        if (! in_array($this->fiscalYear, $availableFiscalYears, true)) {
            $this->fiscalYear = $currentFiscalYear;
        }

        $preferenceService->putFiscalYear($user, self::PREFERENCE_SECTION, $this->fiscalYear);
        $period = $fiscalYearService->period($this->fiscalYear, $fiscalYearStartMonth);
        $totals = BudgetActualEntry::query()
            ->forOrganization($organization->id)
            ->whereBetween('budget_actual_entries.period', [
                $period['start']->toDateString(),
                $period['end']->toDateString(),
            ])
            ->join('budget_actual_accounts', 'budget_actual_accounts.id', '=', 'budget_actual_entries.budget_actual_account_id')
            ->toBase()
            ->selectRaw("COALESCE(SUM(CASE WHEN budget_actual_entries.type = 'budget' AND budget_actual_accounts.account_type = 'revenue' THEN budget_actual_entries.amount ELSE 0 END), 0) AS budget_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN budget_actual_entries.type = 'budget' AND budget_actual_accounts.account_type = 'expense' THEN budget_actual_entries.amount ELSE 0 END), 0) AS budget_expenses")
            ->selectRaw("COALESCE(SUM(CASE WHEN budget_actual_entries.type = 'actual' AND budget_actual_accounts.account_type = 'revenue' THEN budget_actual_entries.amount ELSE 0 END), 0) AS actual_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN budget_actual_entries.type = 'actual' AND budget_actual_accounts.account_type = 'expense' THEN budget_actual_entries.amount ELSE 0 END), 0) AS actual_expenses")
            ->selectRaw("SUM(CASE WHEN budget_actual_entries.type = 'budget' THEN 1 ELSE 0 END) AS budget_records")
            ->selectRaw("SUM(CASE WHEN budget_actual_entries.type = 'actual' THEN 1 ELSE 0 END) AS actual_records")
            ->first();

        return view('livewire.dashboard.budget-actual-summary', [
            'availableFiscalYears' => $availableFiscalYears,
            'budgetActualSummary' => $this->summary($totals, $period['start']->toDateString(), $period['end']->toDateString()),
        ]);
    }

    /**
     * @return array{
     *     periodStart: string,
     *     periodEnd: string,
     *     budget: array{records: int, revenue: string, expenses: string, profitOrLoss: string, heightPercentage: string},
     *     actual: array{records: int, revenue: string, expenses: string, profitOrLoss: string, heightPercentage: string},
     *     variance: string|null
     * }
     */
    private function summary(object $totals, string $periodStart, string $periodEnd): array
    {
        $budgetRevenue = (float) $totals->budget_revenue;
        $budgetExpenses = (float) $totals->budget_expenses;
        $actualRevenue = (float) $totals->actual_revenue;
        $actualExpenses = (float) $totals->actual_expenses;
        $budgetProfitOrLoss = $budgetRevenue - $budgetExpenses;
        $actualProfitOrLoss = $actualRevenue - $actualExpenses;
        $maximum = max(abs($budgetProfitOrLoss), abs($actualProfitOrLoss));
        $height = static function (float $amount) use ($maximum): string {
            if ($maximum <= 0) {
                return '0.00';
            }

            return number_format((abs($amount) / $maximum) * 100, 2, '.', '');
        };
        $budgetRecords = (int) $totals->budget_records;
        $actualRecords = (int) $totals->actual_records;

        return [
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'budget' => [
                'records' => $budgetRecords,
                'revenue' => number_format($budgetRevenue, 2, '.', ''),
                'expenses' => number_format($budgetExpenses, 2, '.', ''),
                'profitOrLoss' => number_format($budgetProfitOrLoss, 2, '.', ''),
                'heightPercentage' => $height($budgetProfitOrLoss),
            ],
            'actual' => [
                'records' => $actualRecords,
                'revenue' => number_format($actualRevenue, 2, '.', ''),
                'expenses' => number_format($actualExpenses, 2, '.', ''),
                'profitOrLoss' => number_format($actualProfitOrLoss, 2, '.', ''),
                'heightPercentage' => $height($actualProfitOrLoss),
            ],
            'variance' => $budgetRecords > 0 && $actualRecords > 0
                ? number_format($actualProfitOrLoss - $budgetProfitOrLoss, 2, '.', '')
                : null,
        ];
    }

    private function fiscalYearStartMonth(Organization $organization): int
    {
        return (int) $organization->company->fiscal_year_start_month;
    }

    private function organization(User $user): Organization
    {
        return $user->organization()
            ->with('company:id,name,fiscal_year_start_month')
            ->firstOrFail();
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}
