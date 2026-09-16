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
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    /** @var list<int> */
    private const array PER_PAGE_OPTIONS = [50, 100, 150, 200];

    /** @var array<string, string> */
    private const array SORT_DIRECTION_OPTIONS = [
        'desc' => '仕訳日の降順',
        'asc' => '仕訳日の昇順',
    ];

    private const string DEFAULT_SORT_DIRECTION = 'desc';

    private const string PREFERENCES_CACHE_PREFIX = 'journal-entries:index-preferences:user:';

    public function index(Request $request): View
    {
        $organizationId = $request->user()->organization_id;
        $fiscalYearStartMonth = (int) $request->user()->company()->value('fiscal_year_start_month');
        $currentFiscalYear = $this->fiscalYearFor(CarbonImmutable::today(), $fiscalYearStartMonth);
        $baseQuery = JournalEntry::query()->forOrganization($organizationId);
        $availableYears = (clone $baseQuery)
            ->select('entry_date')
            ->distinct()
            ->orderByDesc('entry_date')
            ->pluck('entry_date')
            ->map(fn ($entryDate): int => $this->fiscalYearFor(
                CarbonImmutable::parse($entryDate),
                $fiscalYearStartMonth,
            ))
            ->push($currentFiscalYear)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $preferencesCacheKey = self::PREFERENCES_CACHE_PREFIX.$request->user()->getAuthIdentifier();
        $defaultPreferences = [
            'year' => $currentFiscalYear,
            'month' => null,
            'per_page' => self::PER_PAGE_OPTIONS[0],
            'sort_direction' => self::DEFAULT_SORT_DIRECTION,
        ];

        if ($request->boolean('reset_filters')) {
            Cache::forget($preferencesCacheKey);
        }

        $storedPreferences = Cache::get($preferencesCacheKey, []);
        $requestedPreferences = match (true) {
            $request->boolean('reset_filters') => $defaultPreferences,
            $request->hasAny(['year', 'month', 'per_page', 'sort_direction']) => array_replace(
                $defaultPreferences,
                $request->only(['year', 'month', 'per_page', 'sort_direction']),
            ),
            is_array($storedPreferences) && $storedPreferences !== [] => array_replace(
                $defaultPreferences,
                $storedPreferences,
            ),
            default => $defaultPreferences,
        };

        $selectedYear = $this->selectedYear(
            $requestedPreferences['year'],
            $availableYears,
            $currentFiscalYear,
        );
        $selectedMonth = $this->selectedMonth($requestedPreferences['month'] ?? null, $selectedYear);
        $selectedPerPage = $this->selectedPerPage($requestedPreferences['per_page']);
        $selectedSortDirection = $this->selectedSortDirection($requestedPreferences['sort_direction']);

        $normalizedPreferences = [
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'per_page' => $selectedPerPage,
            'sort_direction' => $selectedSortDirection,
        ];

        if ($storedPreferences !== $normalizedPreferences) {
            Cache::forever($preferencesCacheKey, $normalizedPreferences);
        }

        $dateRange = $selectedYear === null
            ? null
            : $this->fiscalYearDateRange(
                $selectedYear,
                $fiscalYearStartMonth,
                $selectedMonth,
            );

        $journalEntries = $baseQuery
            ->when($dateRange !== null, fn ($query) => $query->whereBetween('entry_date', $dateRange))
            ->with('lines:id,journal_entry_id,side,amount')
            ->withCount(['lines', 'documents'])
            ->orderBy('entry_date', $selectedSortDirection)
            ->orderBy('id', $selectedSortDirection)
            ->paginate($selectedPerPage)
            ->withQueryString();

        return view('journal-entries.index', [
            'journalEntries' => $journalEntries,
            'availableYears' => $availableYears,
            'currentFiscalYear' => $currentFiscalYear,
            'fiscalYearMonths' => array_merge(
                range($fiscalYearStartMonth, 12),
                $fiscalYearStartMonth === 1 ? [] : range(1, $fiscalYearStartMonth - 1),
            ),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'sortDirectionOptions' => self::SORT_DIRECTION_OPTIONS,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedPerPage' => $selectedPerPage,
            'selectedSortDirection' => $selectedSortDirection,
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
    private function selectedYear(mixed $value, array $availableYears, int $currentFiscalYear): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $selectedYear = filter_var($value, FILTER_VALIDATE_INT);

        return $selectedYear !== false && in_array($selectedYear, $availableYears, true)
            ? $selectedYear
            : $currentFiscalYear;
    }

    private function selectedMonth(mixed $value, ?int $selectedYear): ?int
    {
        if ($selectedYear === null) {
            return null;
        }

        $selectedMonth = filter_var($value, FILTER_VALIDATE_INT);

        return $selectedMonth !== false && $selectedMonth >= 1 && $selectedMonth <= 12
            ? $selectedMonth
            : null;
    }

    private function selectedPerPage(mixed $value): int
    {
        $selectedPerPage = filter_var($value, FILTER_VALIDATE_INT);

        return $selectedPerPage !== false && in_array($selectedPerPage, self::PER_PAGE_OPTIONS, true)
            ? $selectedPerPage
            : self::PER_PAGE_OPTIONS[0];
    }

    private function selectedSortDirection(mixed $value): string
    {
        return is_string($value) && array_key_exists($value, self::SORT_DIRECTION_OPTIONS)
            ? $value
            : self::DEFAULT_SORT_DIRECTION;
    }

    private function fiscalYearFor(CarbonImmutable $date, int $fiscalYearStartMonth): int
    {
        return $date->month >= $fiscalYearStartMonth
            ? $date->year
            : $date->year - 1;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fiscalYearDateRange(
        int $fiscalYear,
        int $fiscalYearStartMonth,
        ?int $selectedMonth,
    ): array {
        if ($selectedMonth === null) {
            $periodStart = CarbonImmutable::create($fiscalYear, $fiscalYearStartMonth, 1);

            return [
                $periodStart->toDateString(),
                $periodStart->addYear()->subDay()->toDateString(),
            ];
        }

        $calendarYear = $selectedMonth >= $fiscalYearStartMonth
            ? $fiscalYear
            : $fiscalYear + 1;
        $periodStart = CarbonImmutable::create($calendarYear, $selectedMonth, 1);

        return [
            $periodStart->toDateString(),
            $periodStart->endOfMonth()->toDateString(),
        ];
    }
}
