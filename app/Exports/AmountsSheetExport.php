<?php

namespace App\Exports;

use App\Models\MonthlyAmount;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * @implements WithMapping<MonthlyAmount>
 */
class AmountsSheetExport extends DefaultValueBinder implements FromQuery, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly int $organizationId) {}

    /**
     * @return Builder<MonthlyAmount>
     */
    public function query(): Builder
    {
        return MonthlyAmount::query()
            ->forOrganization($this->organizationId)
            ->with([
                'department:id,code',
                'managementAccount:id,code',
            ])
            ->orderByDesc('period')
            ->orderByDesc('id');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Period',
            'Year',
            'Month',
            'DepartmentCode',
            'ManagementAccountCode',
            'Type',
            'Amount',
            'Memo',
        ];
    }

    /**
     * @return array{float, int, int, string, string, string, float, ?string}
     */
    public function map(mixed $row): array
    {
        /** @var MonthlyAmount $row */
        return [
            Date::dateTimeToExcel($row->period),
            $row->period->year,
            $row->period->month,
            $row->department->code,
            $row->managementAccount->code,
            $row->type,
            (float) $row->amount,
            $row->memo,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'A' => 'yyyy-mm',
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return '予実管理';
    }

    /**
     * @return array<string, float|int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 10,
            'C' => 10,
            'D' => 18,
            'E' => 18,
            'F' => 12,
            'G' => 16,
            'H' => 40,
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        $stringColumns = ['D', 'E', 'F', 'H'];

        if ($cell->getRow() === 1 || in_array($cell->getColumn(), $stringColumns, true)) {
            $cell->setValueExplicit(
                $value,
                $value === null ? DataType::TYPE_NULL : DataType::TYPE_STRING,
            );

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
