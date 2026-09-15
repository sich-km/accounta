<?php

namespace App\Exports;

use App\Enums\JournalSide;
use App\Models\JournalEntryLine;
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

/** @implements WithMapping<JournalEntryLine> */
class JournalEntriesSheetExport extends DefaultValueBinder implements FromQuery, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly int $organizationId) {}

    /** @return Builder<JournalEntryLine> */
    public function query(): Builder
    {
        return JournalEntryLine::query()
            ->forOrganization($this->organizationId)
            ->select('journal_entry_lines.*')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->with([
                'journalEntry' => fn ($query) => $query->withCount('documents'),
                'ledgerAccount:id,code,name',
                'department:id,code',
            ])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.line_number');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return [
            'JournalEntryId', 'EntryDate', 'Description', 'LineNumber',
            'LedgerAccountCode', 'LedgerAccountName', 'DepartmentCode',
            'DebitAmount', 'CreditAmount', 'LineDescription', 'Notes', 'DocumentCount',
        ];
    }

    /** @return array{int, float, string, int, string, string, ?string, ?float, ?float, ?string, ?string, int} */
    public function map(mixed $row): array
    {
        /** @var JournalEntryLine $row */
        return [
            $row->journal_entry_id,
            Date::dateTimeToExcel($row->journalEntry->entry_date),
            $row->journalEntry->description,
            $row->line_number,
            $row->ledgerAccount->code,
            $row->ledgerAccount->name,
            $row->department?->code,
            $row->side === JournalSide::Debit ? (float) $row->amount : null,
            $row->side === JournalSide::Credit ? (float) $row->amount : null,
            $row->description,
            $row->journalEntry->notes,
            $row->journalEntry->documents_count,
        ];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'B' => 'yyyy-mm-dd',
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function title(): string
    {
        return 'JournalEntries';
    }

    /** @return array<string, float|int> */
    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 16, 'C' => 32, 'D' => 14, 'E' => 22, 'F' => 28,
            'G' => 18, 'H' => 18, 'I' => 18, 'J' => 32, 'K' => 40, 'L' => 16,
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getRow() === 1 || in_array($cell->getColumn(), ['C', 'E', 'F', 'G', 'J', 'K'], true)) {
            $cell->setValueExplicit($value, $value === null ? DataType::TYPE_NULL : DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
