<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">固定資産管理台帳</h2>
            <a href="{{ route('fixed-assets.create') }}" class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                新規登録
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">資産コード</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">資産名</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">資産区分</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">部門</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">取得価額</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">減価償却累計額</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">帳簿価額</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">状態</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($fixedAssets as $fixedAsset)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-medium text-gray-900">{{ $fixedAsset->asset_code }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-700">{{ $fixedAsset->asset_name }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $fixedAsset->assetCategoryLabel() }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $fixedAsset->department->code }} {{ $fixedAsset->department->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-700">{{ number_format((float) $fixedAsset->acquisition_cost, 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-700">{{ number_format((float) $fixedAsset->accumulated_depreciation, 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-900">{{ number_format((float) $fixedAsset->bookValue(), 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ \App\Models\FixedAsset::STATUSES[$fixedAsset->status] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('fixed-assets.show', $fixedAsset) }}" class="text-sky-600 hover:text-sky-900">詳細</a>
                                            <a href="{{ route('fixed-assets.edit', $fixedAsset) }}" class="text-indigo-600 hover:text-indigo-900">編集</a>
                                            <form method="POST" action="{{ route('fixed-assets.destroy', $fixedAsset) }}" onsubmit="return confirm('この固定資産を削除しますか？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500">固定資産が登録されていません。</td>
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
