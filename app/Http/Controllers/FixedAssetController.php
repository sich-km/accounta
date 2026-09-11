<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFixedAssetRequest;
use App\Http\Requests\UpdateFixedAssetRequest;
use App\Models\Department;
use App\Models\FixedAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FixedAssetController extends Controller
{
    public function index(Request $request): View
    {
        $fixedAssets = FixedAsset::query()
            ->forOrganization($request->user()->organization_id)
            ->with('department')
            ->orderBy('asset_code')
            ->orderBy('id')
            ->paginate(20);

        return view('fixed-assets.index', compact('fixedAssets'));
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
}
