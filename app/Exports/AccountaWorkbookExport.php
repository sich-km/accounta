<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AccountaWorkbookExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly int $organizationId,
        private readonly bool $includeOrganizationSheet,
    ) {}

    /**
     * @return list<Export>
     */
    public function sheets(): array
    {
        $sheets = [
            new AmountsSheetExport($this->organizationId),
            new FixedAssetsSheetExport($this->organizationId),
            new JournalEntriesSheetExport($this->organizationId),
            new DepartmentsSheetExport($this->organizationId),
            new ManagementAccountsSheetExport($this->organizationId),
        ];

        if ($this->includeOrganizationSheet) {
            $sheets[] = new OrganizationSheetExport($this->organizationId);
        }

        return $sheets;
    }
}
