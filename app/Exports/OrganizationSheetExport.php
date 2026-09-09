<?php

namespace App\Exports;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * @implements WithMapping<Organization>
 */
class OrganizationSheetExport extends DefaultValueBinder implements FromQuery, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly int $organizationId) {}

    /**
     * @return Builder<Organization>
     */
    public function query(): Builder
    {
        return Organization::query()
            ->whereKey($this->organizationId)
            ->with('company:id,code,name,fiscal_year_start_month')
            ->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'CompanyCode',
            'CompanyName',
            'FiscalYearStartMonth',
            'OrganizationName',
            'OrganizationType',
        ];
    }

    /**
     * @return array{string, string, int, string, string}
     */
    public function map(mixed $row): array
    {
        /** @var Organization $row */
        return [
            $row->company->code,
            $row->company->name,
            $row->company->fiscal_year_start_month,
            $row->name,
            $row->type,
        ];
    }

    public function title(): string
    {
        return 'Organization';
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getRow() === 1 || in_array($cell->getColumn(), ['A', 'B', 'D', 'E'], true)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
