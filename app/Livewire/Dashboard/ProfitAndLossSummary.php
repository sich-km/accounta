<?php

namespace App\Livewire\Dashboard;

use App\Models\JournalEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\FiscalYearService;
use App\Services\ProfitAndLossSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfitAndLossSummary extends Component
{
    public int $fiscalYear;

    public function mount(FiscalYearService $fiscalYearService): void
    {
        $organization = $this->organization();
        $this->fiscalYear = $fiscalYearService->current($this->fiscalYearStartMonth($organization));
    }

    public function render(
        FiscalYearService $fiscalYearService,
        ProfitAndLossSummaryService $profitAndLossSummaryService,
    ): View {
        $organization = $this->organization();
        $fiscalYearStartMonth = $this->fiscalYearStartMonth($organization);
        $availableFiscalYears = $fiscalYearService->availableYears(
            JournalEntry::query()
                ->forOrganization($organization->id)
                ->distinct()
                ->pluck('entry_date'),
            $fiscalYearStartMonth,
        );
        $this->normalizeFiscalYear($availableFiscalYears, $fiscalYearService->current($fiscalYearStartMonth));
        $profitAndLossSummary = $profitAndLossSummaryService->summarizeFiscalYear(
            $organization->id,
            $fiscalYearStartMonth,
            $this->fiscalYear,
        );
        $journalEntriesCount = JournalEntry::query()
            ->forOrganization($organization->id)
            ->whereBetween('entry_date', [
                $profitAndLossSummary['periodStart'],
                $profitAndLossSummary['periodEnd'],
            ])
            ->count();

        return view('livewire.dashboard.profit-and-loss-summary', [
            'availableFiscalYears' => $availableFiscalYears,
            'journalEntriesCount' => $journalEntriesCount,
            'profitAndLossSummary' => $profitAndLossSummary,
            'profitAndLossChart' => $this->chart($profitAndLossSummary),
        ]);
    }

    /**
     * @param  array<string, string|int>  $summary
     * @return array<string, string>
     */
    private function chart(array $summary): array
    {
        $revenue = max((float) $summary['totalRevenue'], 0);
        $expenses = max((float) $summary['expensesBeforeTax'], 0);
        $maximum = max($revenue, $expenses);
        $height = static function (float $amount) use ($maximum): string {
            if ($amount <= 0 || $maximum <= 0) {
                return '0.00';
            }

            return number_format(($amount / $maximum) * 100, 2, '.', '');
        };

        return [
            'revenue' => (string) $summary['totalRevenue'],
            'expenses' => (string) $summary['expensesBeforeTax'],
            'profitOrLoss' => (string) $summary['profitBeforeTax'],
            'revenueHeightPercentage' => $height($revenue),
            'expenseHeightPercentage' => $height($expenses),
        ];
    }

    /** @param list<int> $availableFiscalYears */
    private function normalizeFiscalYear(array $availableFiscalYears, int $defaultFiscalYear): void
    {
        if (! in_array($this->fiscalYear, $availableFiscalYears, true)) {
            $this->fiscalYear = $defaultFiscalYear;
        }
    }

    private function fiscalYearStartMonth(Organization $organization): int
    {
        return (int) $organization->company->fiscal_year_start_month;
    }

    private function organization(): Organization
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user->organization()
            ->with('company:id,name,fiscal_year_start_month')
            ->firstOrFail();
    }
}
