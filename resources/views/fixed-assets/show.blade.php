<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">固定資産詳細</h2>
                <p class="mt-1 text-base text-gray-700">{{ $fixedAsset->asset_code }} {{ $fixedAsset->asset_name }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-back-link href="{{ route('fixed-assets.index') }}" class="text-sm">一覧へ戻る</x-back-link>
                <x-button href="{{ route('fixed-assets.edit', $fixedAsset) }}" class="text-sm">編集</x-button>
                <x-danger-button type="button" class="text-sm" @click="$dispatch('open-modal', 'confirm-fixed-asset-deletion')">
                    削除
                </x-danger-button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-base font-medium text-green-900">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
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
                            <dt class="text-sm font-semibold text-gray-700">{{ $label }}</dt>
                            <dd class="mt-1 text-base font-medium text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="border-b border-gray-200 px-6 py-4 sm:col-span-2">
                        <dt class="text-sm font-semibold text-gray-700">備考</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-base text-gray-900">{{ $fixedAsset->notes ?? '未設定' }}</dd>
                    </div>
                </dl>
            </div>

        </div>

        <x-confirmation-modal id="confirm-fixed-asset-deletion" max-width="md" :standalone="true">
            <x-slot name="title">固定資産の削除</x-slot>

            <x-slot name="content">
                <p>
                    <span class="font-semibold text-gray-900">{{ $fixedAsset->asset_code }} {{ $fixedAsset->asset_name }}</span>
                    を削除します。この操作は取り消せません。
                </p>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button type="button" @click="$dispatch('close')">
                    キャンセル
                </x-secondary-button>

                <form method="POST" action="{{ route('fixed-assets.destroy', $fixedAsset) }}" class="ms-3">
                    @csrf
                    @method('DELETE')
                    <x-danger-button type="submit">
                        削除する
                    </x-danger-button>
                </form>
            </x-slot>
        </x-confirmation-modal>
    </div>
</x-app-layout>
