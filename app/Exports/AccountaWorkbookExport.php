<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AccountaWorkbookExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly int $organizationId) {}

    /**
     * @return list<Export>
     */
    public function sheets(): array
    {
        return [
            new AmountsSheetExport($this->organizationId),
            new DepartmentsSheetExport($this->organizationId),
            new AccountsSheetExport($this->organizationId),
            new OrganizationSheetExport($this->organizationId),
        ];
    }
}
