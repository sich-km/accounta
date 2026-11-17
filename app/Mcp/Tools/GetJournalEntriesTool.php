<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\McpContext;
use App\Models\JournalDocument;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Organization;
use App\Services\FiscalYearService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-journal-entries')]
#[Description('接続中のAccounta利用者が所属するOrganizationの仕訳帳を参照します。会計年度・月による一覧取得と、仕訳IDによる1件取得に対応します。')]
#[IsReadOnly]
#[IsIdempotent]
class GetJournalEntriesTool extends Tool
{
    public function __construct(
        private McpContext $context,
        private FiscalYearService $fiscalYearService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'journal_entry_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'between:1,200'],
        ]);

        $organization = $this->context->organization($request);

        if (! $organization instanceof Organization) {
            return Response::error('利用者の所属Organizationを確認できません。');
        }

        if (isset($validated['journal_entry_id'])) {
            return $this->singleEntry($organization, (int) $validated['journal_entry_id']);
        }

        return $this->entryList(
            $organization,
            isset($validated['year']) ? (int) $validated['year'] : null,
            isset($validated['month']) ? (int) $validated['month'] : null,
            isset($validated['limit']) ? (int) $validated['limit'] : 50,
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'year' => $schema->integer()
                ->min(1)
                ->max(9999)
                ->description('会計年度。省略時は現在の会計年度です。'),
            'month' => $schema->integer()
                ->min(1)
                ->max(12)
                ->description('会計月（1から12）。yearを省略した場合は無視されます。'),
            'journal_entry_id' => $schema->integer()
                ->min(1)
                ->description('取得する仕訳ID。指定時は期間とlimitを無視して1件だけ取得します。'),
            'limit' => $schema->integer()
                ->min(1)
                ->max(200)
                ->default(50)
                ->description('一覧で返す最大件数。既定50件、最大200件です。'),
        ];
    }

    private function singleEntry(Organization $organization, int $journalEntryId): Response|ResponseFactory
    {
        $entry = $this->baseQuery($organization)->find($journalEntryId);

        if (! $entry instanceof JournalEntry) {
            return Response::error('仕訳が見つかりません。');
        }

        $entryDate = CarbonImmutable::instance($entry->entry_date);
        $fiscalYearStartMonth = (int) $organization->company->fiscal_year_start_month;

        return Response::structured([
            'period' => [
                'fiscal_year' => $this->fiscalYearService->forDate($entryDate, $fiscalYearStartMonth),
                'month' => $entryDate->month,
                'start_date' => $entryDate->format('Y-m-d'),
                'end_date' => $entryDate->format('Y-m-d'),
            ],
            'count' => 1,
            'journal_entries' => [$this->transformEntry($entry)],
        ]);
    }

    private function entryList(
        Organization $organization,
        ?int $requestedFiscalYear,
        ?int $requestedMonth,
        int $limit,
    ): ResponseFactory {
        $fiscalYearStartMonth = (int) $organization->company->fiscal_year_start_month;
        $fiscalYear = $requestedFiscalYear
            ?? $this->fiscalYearService->current($fiscalYearStartMonth);
        $period = $this->fiscalYearService->period($fiscalYear, $fiscalYearStartMonth);
        $month = $requestedFiscalYear === null ? null : $requestedMonth;

        if ($month !== null) {
            $monthOffset = ($month - $fiscalYearStartMonth + 12) % 12;
            $period['start'] = $period['start']->addMonths($monthOffset)->startOfMonth();
            $period['end'] = $period['start']->endOfMonth();
        }

        /** @var Collection<int, JournalEntry> $entries */
        $entries = $this->baseQuery($organization)
            ->whereBetween('entry_date', [$period['start'], $period['end']])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return Response::structured([
            'period' => [
                'fiscal_year' => $fiscalYear,
                'month' => $month,
                'start_date' => $period['start']->format('Y-m-d'),
                'end_date' => $period['end']->format('Y-m-d'),
            ],
            'count' => $entries->count(),
            'journal_entries' => $entries
                ->map(fn (JournalEntry $entry): array => $this->transformEntry($entry))
                ->values()
                ->all(),
        ]);
    }

    /** @return Builder<JournalEntry> */
    private function baseQuery(Organization $organization): Builder
    {
        return JournalEntry::query()
            ->forOrganization((int) $organization->getKey())
            ->with([
                'originatingDepartment',
                'lines.ledgerAccount',
                'lines.department',
                'documents',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEntry(JournalEntry $entry): array
    {
        return [
            'id' => $entry->getKey(),
            'entry_date' => $entry->entry_date->format('Y-m-d'),
            'description' => $entry->description,
            'notes' => $entry->notes,
            'originating_department_code' => $entry->originatingDepartment?->code,
            'debit_total' => $entry->debitTotal(),
            'credit_total' => $entry->creditTotal(),
            'lines' => $entry->lines
                ->map(fn (JournalEntryLine $line): array => [
                    'side' => $line->side->value,
                    'ledger_account_code' => $line->ledgerAccount->code,
                    'ledger_account_name' => $line->ledgerAccount->name,
                    'department_code' => $line->department?->code,
                    'amount' => $line->amount,
                    'description' => $line->description,
                ])
                ->values()
                ->all(),
            'documents' => $entry->documents
                ->map(fn (JournalDocument $document): array => [
                    'id' => $document->getKey(),
                    'journal_entry_id' => $document->journal_entry_id,
                    'original_filename' => $document->original_name,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'registered_at' => $document->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
