<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">固定資産詳細</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $fixedAsset->asset_code }} {{ $fixedAsset->asset_name }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('fixed-assets.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">一覧へ戻る</a>
                <a href="{{ route('fixed-assets.edit', $fixedAsset) }}" class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">編集</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <dl class="grid grid-cols-1 sm:grid-cols-2">
                    @php
                        $details = [
                            '資産コード' => $fixedAsset->asset_code,
                            '資産名' => $fixedAsset->asset_name,
                            '資産区分' => $fixedAsset->assetCategoryLabel(),
                            '部門' => $fixedAsset->department->code.' '.$fixedAsset->department->name,
                            '取得日' => $fixedAsset->acquisition_date->format('Y/m/d'),
                            '供用開始日' => $fixedAsset->service_start_date?->format('Y/m/d') ?? '未設定',
                            '取得価額' => number_format((float) $fixedAsset->acquisition_cost, 2),
                            '耐用年数' => $fixedAsset->useful_life_years === null ? '未設定' : $fixedAsset->useful_life_years.'年',
                            '償却方法' => \App\Models\FixedAsset::DEPRECIATION_METHODS[$fixedAsset->depreciation_method],
                            '残存価額' => number_format((float) $fixedAsset->residual_value, 2),
                            '当期減価償却費' => number_format((float) $fixedAsset->current_period_depreciation_expense, 2),
                            '減価償却累計額' => number_format((float) $fixedAsset->accumulated_depreciation, 2),
                            '帳簿価額' => number_format((float) $fixedAsset->bookValue(), 2),
                            '資産状態' => \App\Models\FixedAsset::STATUSES[$fixedAsset->status],
                            '登録日時' => $fixedAsset->created_at->format('Y/m/d H:i:s'),
                            '更新日時' => $fixedAsset->updated_at->format('Y/m/d H:i:s'),
                        ];
                    @endphp
                    @foreach ($details as $label => $value)
                        <div class="border-b border-gray-200 px-6 py-4 sm:even:border-l">
                            <dt class="text-sm font-medium text-gray-500">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="border-b border-gray-200 px-6 py-4 sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">備考</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-sm text-gray-900">{{ $fixedAsset->notes ?? '未設定' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex justify-end">
                <form method="POST" action="{{ route('fixed-assets.destroy', $fixedAsset) }}" onsubmit="return confirm('この固定資産を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button type="submit">削除</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
