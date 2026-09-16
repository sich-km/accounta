<?php

namespace App\Livewire\Dashboard;

use App\Models\JournalEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\DashboardFiscalYearPreferenceService;
use App\Services\FiscalYearService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BalanceSheetSummary extends Component
{
    private const string PREFERENCE_SECTION = 'balance-sheet';

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

        $preferenceService->putFiscalYear($user, self::PREFERENCE_SECTION, $this->fiscalYear);
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
