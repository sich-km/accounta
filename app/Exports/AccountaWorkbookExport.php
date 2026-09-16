<?php

namespace App\Exports;

use App\Services\FiscalYearService;
use App\Services\ProfitAndLossSummaryService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AccountaWorkbookExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly int $organizationId,
        private readonly bool $includeOrganizationDetails,
        private readonly FiscalYearService $fiscalYearService,
        private readonly ProfitAndLossSummaryService $profitAndLossSummaryService,
    ) {}

    /**
     * @return list<Export>
     */
    public function sheets(): array
    {
        $sheets = [
            new SummarySheetExport(
                $this->organizationId,
                $this->fiscalYearService,
                $this->profitAndLossSummaryService,
            ),
            new JournalEntriesSheetExport($this->organizationId),
            new FixedAssetsSheetExport($this->organizationId),
            new BudgetActualEntriesSheetExport($this->organizationId),
            new DepartmentsSheetExport($this->organizationId),
            new BudgetActualAccountsSheetExport($this->organizationId),
        ];

        if ($this->includeOrganizationDetails) {
            $sheets[] = new OrganizationSheetExport($this->organizationId);
        }

        return $sheets;
    }
}
