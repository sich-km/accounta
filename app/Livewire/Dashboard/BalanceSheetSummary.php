<?php

namespace App\Livewire\Dashboard;

use App\Models\JournalEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\FiscalYearService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BalanceSheetSummary extends Component
{
    public int $fiscalYear;

    public function mount(FiscalYearService $fiscalYearService): void
    {
        $organization = $this->organization();
        $this->fiscalYear = $fiscalYearService->current($this->fiscalYearStartMonth($organization));
    }

    public function render(FiscalYearService $fiscalYearService): View
    {
        $organization = $this->organization();
        $fiscalYearStartMonth = $this->fiscalYearStartMonth($organization);
        $availableFiscalYears = $fiscalYearService->availableYears(
            JournalEntry::query()
                ->forOrganization($organization->id)
                ->distinct()
                ->pluck('entry_date'),
            $fiscalYearStartMonth,
        );
        $currentFiscalYear = $fiscalYearService->current($fiscalYearStartMonth);

        if (! in_array($this->fiscalYear, $availableFiscalYears, true)) {
            $this->fiscalYear = $currentFiscalYear;
        }

        $period = $fiscalYearService->period($this->fiscalYear, $fiscalYearStartMonth);

        return view('livewire.dashboard.balance-sheet-summary', [
            'availableFiscalYears' => $availableFiscalYears,
            'periodEnd' => $period['end']->toDateString(),
        ]);
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
