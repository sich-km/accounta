<?php

namespace App\Exports;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * @implements WithMapping<Account>
 */
class AccountsSheetExport extends DefaultValueBinder implements FromQuery, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly int $organizationId) {}

    /**
     * @return Builder<Account>
     */
    public function query(): Builder
    {
        return Account::query()
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
            'AccountCode',
            'AccountName',
            'AccountType',
            'IsActive',
        ];
    }

    /**
     * @return array{string, string, string, bool}
     */
    public function map(mixed $row): array
    {
        /** @var Account $row */
        return [
            $row->code,
            $row->name,
            $row->account_type,
            $row->is_active,
        ];
    }

    public function title(): string
    {
        return '勘定科目マスタ';
    }

    /**
     * @return array<string, float|int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 32,
            'C' => 18,
            'D' => 12,
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getRow() === 1 || in_array($cell->getColumn(), ['A', 'B', 'C'], true)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
