<?php

namespace App\Exports;

use App\Models\Department;
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
 * @implements WithMapping<Department>
 */
class DepartmentsSheetExport extends DefaultValueBinder implements FromQuery, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly int $organizationId) {}

    /**
     * @return Builder<Department>
     */
    public function query(): Builder
    {
        return Department::query()
            ->forOrganization($this->organizationId)
            ->orderBy('code')
            ->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'DepartmentCode',
            'DepartmentName',
            'IsActive',
        ];
    }

    /**
     * @return array{string, string, bool}
     */
    public function map(mixed $row): array
    {
        /** @var Department $row */
        return [
            $row->code,
            $row->name,
            $row->is_active,
        ];
    }

    public function title(): string
    {
        return 'Departments';
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getRow() === 1 || in_array($cell->getColumn(), ['A', 'B'], true)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
