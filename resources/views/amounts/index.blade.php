<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">予算・実績</h2>
            <a href="{{ route('amounts.create') }}" class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                新規登録
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">年月</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">部門</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">予実管理科目</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">区分</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">金額</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">メモ</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($amounts as $amount)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $amount->period->format('Y/m') }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        {{ $amount->department->code }} {{ $amount->department->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        {{ $amount->managementAccount->code }} {{ $amount->managementAccount->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ \App\Models\MonthlyAmount::TYPES[$amount->type] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-900">{{ number_format((float) $amount->amount, 2) }}</td>
                                    <td class="max-w-xs truncate px-4 py-4 text-sm text-gray-700" title="{{ $amount->memo }}">{{ $amount->memo }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <a href="{{ route('amounts.edit', $amount) }}" class="text-indigo-600 hover:text-indigo-900">編集</a>
                                            <form method="POST" action="{{ route('amounts.destroy', $amount) }}" onsubmit="return confirm('この明細を削除しますか？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">予算・実績明細が登録されていません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $amounts->links() }}
        </div>
    </div>
</x-app-layout>
