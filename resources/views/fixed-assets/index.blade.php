<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">固定資産管理台帳</h2>
            <x-button href="{{ route('fixed-assets.create') }}" class="text-sm">新規登録</x-button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-base font-medium text-green-900">{{ session('status') }}</div>
            @endif

            <div class="bg-white p-3 shadow-sm sm:rounded-lg">
                <form
                    method="GET"
                    action="{{ route('fixed-assets.index') }}"
                    class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
                >
                    <div>
                        <x-label for="acquisition_year" value="取得年度" class="font-semibold text-gray-800" />
                        <select
                            id="acquisition_year"
                            name="acquisition_year"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-32"
                        >
                            <option value="">すべて</option>
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}年</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-label for="asset_category" value="資産区分" class="font-semibold text-gray-800" />
                        <select
                            id="asset_category"
                            name="asset_category"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-56"
                        >
                            <option value="">すべて</option>
                            @foreach ($assetCategories as $assetCategory => $assetCategoryLabel)
                                <option value="{{ $assetCategory }}" @selected($selectedAssetCategory === $assetCategory)>
                                    {{ $assetCategoryLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-label for="department_id" value="部門" class="font-semibold text-gray-800" />
                        <select
                            id="department_id"
                            name="department_id"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-56"
                        >
                            <option value="">すべて</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected($selectedDepartmentId === $department->id)>
                                    {{ $department->code }} {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-label for="status" value="状態" class="font-semibold text-gray-800" />
                        <select
                            id="status"
                            name="status"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-36"
                        >
                            <option value="">すべて</option>
                            @foreach ($statuses as $status => $statusLabel)
                                <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-button type="submit" class="text-sm">表示</x-button>

                        @if ($hasActiveFilters)
                            <a href="{{ route('fixed-assets.index') }}" class="inline-flex items-center px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900">
                                クリア
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <x-list-result-count :count="$fixedAssets->total()" label="固定資産の該当件数" />

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[72rem] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">資産コード</th>
                                <th scope="col" class="min-w-[14rem] px-4 py-3 text-left text-sm font-semibold text-gray-800">資産名</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">資産区分</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">部門</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">取得価額</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">減価償却累計額</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">帳簿価額</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">状態</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($fixedAssets as $fixedAsset)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-base">
                                        <a
                                            href="{{ route('fixed-assets.show', $fixedAsset) }}"
                                            class="font-semibold text-sky-700 hover:text-sky-900"
                                        >
                                            {{ $fixedAsset->asset_code }}
                                        </a>
                                    </td>
                                    <td class="min-w-[14rem] max-w-xs break-words px-4 py-4 text-base text-gray-900">{{ $fixedAsset->asset_name }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-base text-gray-900">{{ $fixedAsset->assetCategoryLabel() }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-base text-gray-900">{{ $fixedAsset->department->code }} {{ $fixedAsset->department->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-base font-semibold text-gray-900">{{ number_format((float) $fixedAsset->acquisition_cost, 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-base font-semibold text-gray-900">{{ number_format((float) $fixedAsset->accumulated_depreciation, 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-base font-semibold text-gray-900">{{ number_format((float) $fixedAsset->bookValue(), 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-base text-gray-900">{{ \App\Models\FixedAsset::STATUSES[$fixedAsset->status] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-base text-gray-700">
                                        {{ $hasActiveFilters ? '条件に一致する固定資産がありません。' : '固定資産が登録されていません。' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $fixedAssets->links() }}
        </div>
    </div>
</x-app-layout>
