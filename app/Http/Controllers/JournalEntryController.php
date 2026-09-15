<?php

namespace App\Http\Controllers;

use App\Enums\JournalSide;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Http\Requests\UpdateJournalEntryRequest;
use App\Models\Department;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Services\JournalEntryService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    /** @var list<int> */
    private const array PER_PAGE_OPTIONS = [50, 100, 150, 200];

    public function index(Request $request): View
    {
        $organizationId = $request->user()->organization_id;
        $baseQuery = JournalEntry::query()->forOrganization($organizationId);
        $availableYears = (clone $baseQuery)
            ->select('entry_date')
            ->distinct()
            ->orderByDesc('entry_date')
            ->pluck('entry_date')
            ->map(fn ($entryDate): int => CarbonImmutable::parse($entryDate)->year)
            ->unique()
            ->values()
            ->all();

        $selectedYear = $this->selectedYear($request, $availableYears);
        $selectedMonth = $this->selectedMonth($request, $selectedYear);
        $selectedPerPage = $request->integer('per_page');

        if (! in_array($selectedPerPage, self::PER_PAGE_OPTIONS, true)) {
            $selectedPerPage = self::PER_PAGE_OPTIONS[0];
        }

        $periodStart = $selectedYear === null
            ? null
            : CarbonImmutable::create($selectedYear, $selectedMonth ?? 1, 1)->startOfDay();
        $dateRange = $periodStart === null
            ? null
            : [
                $periodStart->toDateString(),
                ($selectedMonth === null ? $periodStart->endOfYear() : $periodStart->endOfMonth())->toDateString(),
            ];

        $journalEntries = $baseQuery
            ->when($dateRange !== null, fn ($query) => $query->whereBetween('entry_date', $dateRange))
            ->with('lines:id,journal_entry_id,side,amount')
            ->withCount(['lines', 'documents'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($selectedPerPage)
            ->withQueryString();

        return view('journal-entries.index', [
            'journalEntries' => $journalEntries,
            'availableYears' => $availableYears,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedPerPage' => $selectedPerPage,
        ]);
    }

    public function create(Request $request): View
    {
        return view('journal-entries.create', [
            ...$this->formOptions($request),
            'journalSides' => JournalSide::cases(),
        ]);
    }

    public function store(StoreJournalEntryRequest $request, JournalEntryService $service): RedirectResponse
    {
        /** @var list<UploadedFile> $documents */
        $documents = $request->file('documents', []);
        $journalEntry = $service->create(
            $request->user()->organization_id,
            $request->safe()->except('documents'),
            $documents,
        );

        return redirect()->route('journal-entries.show', $journalEntry)->with('status', '仕訳を登録しました。');
    }

    public function show(Request $request, int $journalEntry): View
    {
        $journalEntry = $this->ownedJournalEntry($request, $journalEntry);
        $journalEntry->load(['originatingDepartment', 'lines.ledgerAccount', 'lines.department', 'documents']);

        return view('journal-entries.show', compact('journalEntry'));
    }

    public function edit(Request $request, int $journalEntry): View
    {
        $journalEntry = $this->ownedJournalEntry($request, $journalEntry);
        $journalEntry->load('lines')->loadCount('documents');

        return view('journal-entries.edit', [
            'journalEntry' => $journalEntry,
            ...$this->formOptions($request, $journalEntry),
            'journalSides' => JournalSide::cases(),
        ]);
    }

    public function update(UpdateJournalEntryRequest $request, int $journalEntry, JournalEntryService $service): RedirectResponse
    {
        $journalEntry = $this->ownedJournalEntry($request, $journalEntry);
        /** @var list<UploadedFile> $documents */
        $documents = $request->file('documents', []);
        $service->update(
            $journalEntry,
            $request->safe()->except('documents'),
            $documents,
        );

        return redirect()->route('journal-entries.show', $journalEntry)->with('status', '仕訳を更新しました。');
    }

    public function destroy(Request $request, int $journalEntry, JournalEntryService $service): RedirectResponse
    {
        $journalEntry = $this->ownedJournalEntry($request, $journalEntry);
        $allFilesDeleted = $service->delete($journalEntry);

        return redirect()->route('journal-entries.index')->with(
            $allFilesDeleted ? 'status' : 'warning',
            $allFilesDeleted ? '仕訳を削除しました。' : '仕訳は削除しましたが、一部の証憑ファイルを削除できませんでした。',
        );
    }

    /**
     * @return array{ledgerAccounts: Collection<int, LedgerAccount>, departments: Collection<int, Department>, userDepartmentId: ?int}
     */
    private function formOptions(Request $request, ?JournalEntry $journalEntry = null): array
    {
        $ledgerAccountIds = $journalEntry?->lines->pluck('ledger_account_id')->all() ?? [];
        $departmentIds = $journalEntry?->lines->pluck('department_id')->filter()->all() ?? [];

        if ($journalEntry?->originating_department_id !== null) {
            $departmentIds[] = $journalEntry->originating_department_id;
        }

        $ledgerAccounts = LedgerAccount::query()
            ->forOrganization($request->user()->organization_id)
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->when($ledgerAccountIds !== [], fn ($activeQuery) => $activeQuery->orWhereIn('id', $ledgerAccountIds)))
            ->orderBy('code')
            ->get();
        $departments = Department::query()
            ->forOrganization($request->user()->organization_id)
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->when($departmentIds !== [], fn ($activeQuery) => $activeQuery->orWhereIn('id', $departmentIds)))
            ->orderBy('code')
            ->get();

        return [
            'ledgerAccounts' => $ledgerAccounts,
            'departments' => $departments,
            'userDepartmentId' => $departments->contains('id', $request->user()->department_id)
                ? $request->user()->department_id
                : null,
        ];
    }

    private function ownedJournalEntry(Request $request, int $journalEntryId): JournalEntry
    {
        return JournalEntry::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($journalEntryId);
    }

    /**
     * @param  list<int>  $availableYears
     */
    private function selectedYear(Request $request, array $availableYears): ?int
    {
        $selectedYear = $request->integer('year');

        return in_array($selectedYear, $availableYears, true) ? $selectedYear : null;
    }

    private function selectedMonth(Request $request, ?int $selectedYear): ?int
    {
        if ($selectedYear === null) {
            return null;
        }

        $selectedMonth = $request->integer('month');

        return $selectedMonth >= 1 && $selectedMonth <= 12
            ? $selectedMonth
            : null;
    }
}
