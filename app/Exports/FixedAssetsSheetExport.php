<?php

namespace App\Exports;

use App\Models\FixedAsset;
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

/** @implements WithMapping<FixedAsset> */
class FixedAssetsSheetExport extends DefaultValueBinder implements FromQuery, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly int $organizationId) {}

    /** @return Builder<FixedAsset> */
    public function query(): Builder
    {
        return FixedAsset::query()
            ->forOrganization($this->organizationId)
            ->with('department:id,code')
            ->orderBy('asset_code')
            ->orderBy('id');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return [
            'AssetCode',
            'AssetName',
            'AssetCategory',
            'AssetCategoryDetail',
            'DepartmentCode',
            'AcquisitionDate',
            'ServiceStartDate',
            'AcquisitionCost',
            'UsefulLifeYears',
            'DepreciationMethod',
            'ResidualValue',
            'CurrentPeriodDepreciationExpense',
            'AccumulatedDepreciation',
            'BookValue',
            'Status',
            'Notes',
        ];
    }

    /** @return array{string, string, string, ?string, string, float, ?float, float, ?int, string, float, float, float, float, string, ?string} */
    public function map(mixed $row): array
    {
        /** @var FixedAsset $row */
        return [
            $row->asset_code,
            $row->asset_name,
            $row->asset_category,
            $row->asset_category_detail,
            $row->department->code,
            Date::dateTimeToExcel($row->acquisition_date),
            $row->service_start_date === null ? null : Date::dateTimeToExcel($row->service_start_date),
            (float) $row->acquisition_cost,
            $row->useful_life_years,
            $row->depreciation_method,
            (float) $row->residual_value,
            (float) $row->current_period_depreciation_expense,
            (float) $row->accumulated_depreciation,
            (float) $row->bookValue(),
            $row->status,
            $row->notes,
        ];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'F' => 'yyyy-mm-dd',
            'G' => 'yyyy-mm-dd',
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return 'FixedAssets';
    }

    /** @return array<string, float|int> */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 32,
            'C' => 20,
            'D' => 24,
            'E' => 18,
            'F' => 16,
            'G' => 16,
            'H' => 18,
            'I' => 16,
            'J' => 24,
            'K' => 18,
            'L' => 34,
            'M' => 24,
            'N' => 18,
            'O' => 14,
            'P' => 40,
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        $stringColumns = ['A', 'B', 'C', 'D', 'E', 'J', 'O', 'P'];

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
