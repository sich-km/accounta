<?php

namespace App\Services;

use App\Models\JournalDocument;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class JournalEntryService
{
    public function __construct(private JournalDocumentService $documentService) {}

    /**
     * @param  array{entry_date: string, originating_department_id: ?int, description: string, notes: ?string, lines: list<array{side: string, ledger_account_id: int, department_id: ?int, amount: string, description: ?string}>}  $data
     * @param  list<UploadedFile>  $documents
     */
    public function create(int $organizationId, array $data, array $documents = []): JournalEntry
    {
        $journalEntry = DB::transaction(function () use ($organizationId, $data): JournalEntry {
            $lines = $this->withDefaultLineDescriptions(
                $organizationId,
                $data['entry_date'],
                $data['lines'],
            );
            $journalEntry = Organization::query()->findOrFail($organizationId)->journalEntries()->create([
                'originating_department_id' => $data['originating_department_id'],
                'entry_date' => $data['entry_date'],
                'description' => $data['description'],
                'notes' => $data['notes'],
            ]);

            $this->replaceLines($journalEntry, $organizationId, $lines);

            return $journalEntry;
        });

        try {
            foreach ($documents as $document) {
                $this->documentService->store($journalEntry, $document);
            }
        } catch (Throwable $exception) {
            $this->delete($journalEntry);

            throw $exception;
        }

        return $journalEntry;
    }

    /**
     * @param  array{entry_date: string, originating_department_id: ?int, description: string, notes: ?string, lines: list<array{side: string, ledger_account_id: int, department_id: ?int, amount: string, description: ?string}>}  $data
     * @param  list<UploadedFile>  $documents
     */
    public function update(JournalEntry $journalEntry, array $data, array $documents = []): JournalEntry
    {
        /** @var list<JournalDocument> $storedDocuments */
        $storedDocuments = [];

        try {
            return DB::transaction(function () use ($journalEntry, $data, $documents, &$storedDocuments): JournalEntry {
                $journalEntry->update([
                    'originating_department_id' => $data['originating_department_id'],
                    'entry_date' => $data['entry_date'],
                    'description' => $data['description'],
                    'notes' => $data['notes'],
                ]);
                $journalEntry->lines()->delete();
                $this->replaceLines($journalEntry, $journalEntry->organization_id, $data['lines']);

                foreach ($documents as $document) {
                    $storedDocuments[] = $this->documentService->store($journalEntry, $document);
                }

                return $journalEntry;
            });
        } catch (Throwable $exception) {
            foreach ($storedDocuments as $document) {
                $this->documentService->delete($document);
            }

            throw $exception;
        }
    }

    public function delete(JournalEntry $journalEntry): bool
    {
        $documents = $journalEntry->documents()->get(['disk', 'path']);
        $allFilesDeleted = true;

        DB::transaction(fn () => $journalEntry->delete());

        foreach ($documents as $document) {
            if (Storage::disk($document->disk)->exists($document->path)
                && ! Storage::disk($document->disk)->delete($document->path)) {
                report(new RuntimeException("仕訳削除後の証憑ファイル削除に失敗しました: {$document->path}"));
                $allFilesDeleted = false;
            }
        }

        return $allFilesDeleted;
    }

    /**
     * @param  list<array{side: string, ledger_account_id: int, department_id: ?int, amount: string, description: ?string}>  $lines
     */
    private function replaceLines(JournalEntry $journalEntry, int $organizationId, array $lines): void
    {
        foreach ($lines as $index => $line) {
            $journalEntry->lines()->create([
                'organization_id' => $organizationId,
                'line_number' => $index + 1,
                'ledger_account_id' => $line['ledger_account_id'],
                'department_id' => $line['department_id'],
                'side' => $line['side'],
                'amount' => $line['amount'],
                'description' => $line['description'],
            ]);
        }
    }

    /**
     * @param  list<array{side: string, ledger_account_id: int, department_id: ?int, amount: string, description: ?string}>  $lines
     * @return list<array{side: string, ledger_account_id: int, department_id: ?int, amount: string, description: ?string}>
     */
    private function withDefaultLineDescriptions(int $organizationId, string $entryDate, array $lines): array
    {
        $ledgerAccountNames = LedgerAccount::query()
            ->forOrganization($organizationId)
            ->whereKey(collect($lines)->pluck('ledger_account_id')->unique()->all())
            ->pluck('name', 'id');
        $date = CarbonImmutable::parse($entryDate);
        $period = $date->format('Y年n月');

        return array_map(function (array $line) use ($ledgerAccountNames, $period): array {
            if (filled($line['description'])) {
                return $line;
            }

            $ledgerAccountName = $ledgerAccountNames->get($line['ledger_account_id']);

            if ($ledgerAccountName !== null) {
                $line['description'] = "{$period}の{$ledgerAccountName}";
            }

            return $line;
        }, $lines);
    }
}
