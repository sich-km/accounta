<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFixedAssetRequest;
use App\Http\Requests\UpdateFixedAssetRequest;
use App\Models\Department;
use App\Models\FixedAsset;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class FixedAssetController extends Controller
{
    private const string PREFERENCES_CACHE_PREFIX = 'fixed-assets:index-preferences:user:';

    public function index(Request $request): View
    {
        $organizationId = $request->user()->organization_id;
        $baseQuery = FixedAsset::query()->forOrganization($organizationId);
        $availableYears = (clone $baseQuery)
            ->select('acquisition_date')
            ->distinct()
            ->orderByDesc('acquisition_date')
            ->pluck('acquisition_date')
            ->map(fn (mixed $acquisitionDate): int => CarbonImmutable::parse($acquisitionDate)->year)
            ->unique()
            ->values()
            ->all();
        $departments = Department::query()
            ->forOrganization($organizationId)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $preferencesCacheKey = self::PREFERENCES_CACHE_PREFIX.$request->user()->getAuthIdentifier();
        $preferenceKeys = ['acquisition_year', 'asset_category', 'department_id', 'status'];
        $defaultPreferences = [
            'acquisition_year' => null,
            'asset_category' => null,
            'department_id' => null,
            'status' => null,
        ];

        if ($request->boolean('reset_filters')) {
            Cache::forget($preferencesCacheKey);
        }

        $storedPreferences = Cache::get($preferencesCacheKey, []);
        $requestedPreferences = match (true) {
            $request->boolean('reset_filters') => $defaultPreferences,
            $request->hasAny($preferenceKeys) => array_replace(
                $defaultPreferences,
                $request->only($preferenceKeys),
            ),
            is_array($storedPreferences) && $storedPreferences !== [] => array_replace(
                $defaultPreferences,
                $storedPreferences,
            ),
            default => $defaultPreferences,
        };

        $selectedYear = $this->selectedInteger($requestedPreferences['acquisition_year'], $availableYears);
        $selectedAssetCategory = $this->selectedKey($requestedPreferences['asset_category'], FixedAsset::ASSET_CATEGORIES);
        $selectedDepartmentId = $this->selectedInteger($requestedPreferences['department_id'], $departments->modelKeys());
        $selectedStatus = $this->selectedKey($requestedPreferences['status'], FixedAsset::STATUSES);

        $normalizedPreferences = [
            'acquisition_year' => $selectedYear,
            'asset_category' => $selectedAssetCategory,
            'department_id' => $selectedDepartmentId,
            'status' => $selectedStatus,
        ];

        if ($storedPreferences !== $normalizedPreferences) {
            Cache::forever($preferencesCacheKey, $normalizedPreferences);
        }

        $fixedAssets = $baseQuery
            ->when($selectedYear !== null, fn (Builder $query): Builder => $query->whereBetween('acquisition_date', [
                sprintf('%d-01-01', $selectedYear),
                sprintf('%d-12-31', $selectedYear),
            ]))
            ->when($selectedAssetCategory !== null, fn (Builder $query): Builder => $query->where('asset_category', $selectedAssetCategory))
            ->when($selectedDepartmentId !== null, fn (Builder $query): Builder => $query->where('department_id', $selectedDepartmentId))
            ->when($selectedStatus !== null, fn (Builder $query): Builder => $query->where('status', $selectedStatus))
            ->with('department')
            ->orderBy('asset_code')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('fixed-assets.index', [
            'fixedAssets' => $fixedAssets,
            'availableYears' => $availableYears,
            'assetCategories' => FixedAsset::ASSET_CATEGORIES,
            'departments' => $departments,
            'statuses' => FixedAsset::STATUSES,
            'selectedYear' => $selectedYear,
            'selectedAssetCategory' => $selectedAssetCategory,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedStatus' => $selectedStatus,
            'hasActiveFilters' => collect([
                $selectedYear,
                $selectedAssetCategory,
                $selectedDepartmentId,
                $selectedStatus,
            ])->contains(fn (mixed $value): bool => $value !== null),
        ]);
    }

    public function create(Request $request): View
    {
        return view('fixed-assets.create', [
            'departments' => $this->departments($request),
            'assetCategories' => FixedAsset::ASSET_CATEGORIES,
            'depreciationMethods' => FixedAsset::DEPRECIATION_METHODS,
            'statuses' => FixedAsset::STATUSES,
        ]);
    }

    public function store(StoreFixedAssetRequest $request): RedirectResponse
    {
        $request->user()
            ->organization
            ->fixedAssets()
            ->create($request->validated());

        return redirect()
            ->route('fixed-assets.index')
            ->with('status', '固定資産を登録しました。');
    }

    public function show(Request $request, int $fixedAsset): View
    {
        $fixedAsset = $this->ownedFixedAsset($request, $fixedAsset);
        $fixedAsset->load('department');

        return view('fixed-assets.show', compact('fixedAsset'));
    }

    public function edit(Request $request, int $fixedAsset): View
    {
        $fixedAsset = $this->ownedFixedAsset($request, $fixedAsset);

        return view('fixed-assets.edit', [
            'fixedAsset' => $fixedAsset,
            'departments' => $this->departments($request, $fixedAsset),
            'assetCategories' => FixedAsset::ASSET_CATEGORIES,
            'depreciationMethods' => FixedAsset::DEPRECIATION_METHODS,
            'statuses' => FixedAsset::STATUSES,
        ]);
    }

    public function update(UpdateFixedAssetRequest $request, int $fixedAsset): RedirectResponse
    {
        $fixedAsset = $this->ownedFixedAsset($request, $fixedAsset);
        $fixedAsset->update($request->validated());

        return redirect()
            ->route('fixed-assets.show', $fixedAsset)
            ->with('status', '固定資産を更新しました。');
    }

    public function destroy(Request $request, int $fixedAsset): RedirectResponse
    {
        $this->ownedFixedAsset($request, $fixedAsset)->delete();

        return redirect()
            ->route('fixed-assets.index')
            ->with('status', '固定資産を削除しました。');
    }

    /** @return Collection<int, Department> */
    private function departments(Request $request, ?FixedAsset $fixedAsset = null): Collection
    {
        return Department::query()
            ->forOrganization($request->user()->organization_id)
            ->where(function ($query) use ($fixedAsset): void {
                $query->where('is_active', true)
                    ->when(
                        $fixedAsset,
                        fn ($activeQuery) => $activeQuery->orWhere('id', $fixedAsset->department_id),
                    );
            })
            ->orderBy('code')
            ->get();
    }

    private function ownedFixedAsset(Request $request, int $fixedAssetId): FixedAsset
    {
        return FixedAsset::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($fixedAssetId);
    }

    /**
     * @param  list<int>  $allowedValues
     */
    private function selectedInteger(mixed $value, array $allowedValues): ?int
    {
        $selectedValue = filter_var($value, FILTER_VALIDATE_INT);

        return $selectedValue !== false && in_array($selectedValue, $allowedValues, true)
            ? $selectedValue
            : null;
    }

    /**
     * @param  array<string, string>  $options
     */
    private function selectedKey(mixed $value, array $options): ?string
    {
        return is_string($value) && array_key_exists($value, $options)
            ? $value
            : null;
    }
}
