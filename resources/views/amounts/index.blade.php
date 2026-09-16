<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">予算・実績</h2>
            <x-button href="{{ route('amounts.create') }}" class="text-sm">新規登録</x-button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-base font-medium text-green-900">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white p-3 shadow-sm sm:rounded-lg">
                <form
                    method="GET"
                    action="{{ route('amounts.index') }}"
                    class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
                    x-data="{
                        selectedYear: {{ Js::from($selectedYear === null ? '' : (string) $selectedYear) }},
                        selectedMonth: {{ Js::from($selectedMonth === null ? '' : (string) $selectedMonth) }},
                    }"
                >
                    <div>
                        <x-label for="type" value="区分" class="font-semibold text-gray-800" />
                        <select
                            id="type"
                            name="type"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-32"
                        >
                            <option value="">すべて</option>
                            @foreach ($amountTypes as $type => $typeLabel)
                                <option value="{{ $type }}" @selected($selectedType === $type)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-label for="year" value="対象年" class="font-semibold text-gray-800" />
                        <select
                            id="year"
                            name="year"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-32"
                            x-model="selectedYear"
                            @change="if (selectedYear === '') selectedMonth = ''"
                        >
                            <option value="">すべて</option>
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}年</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-label for="month" value="対象月" class="font-semibold text-gray-800" />
                        <select
                            id="month"
                            name="month"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-600 sm:w-28"
                            x-model="selectedMonth"
                            x-bind:disabled="selectedYear === ''"
                        >
                            <option value="">すべて</option>
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}" @selected($selectedMonth === $month)>{{ $month }}月</option>
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
                        <x-label for="management_account_id" value="予実管理科目" class="font-semibold text-gray-800" />
                        <select
                            id="management_account_id"
                            name="management_account_id"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-64"
                        >
                            <option value="">すべて</option>
                            @foreach ($managementAccounts as $managementAccount)
                                <option value="{{ $managementAccount->id }}" @selected($selectedManagementAccountId === $managementAccount->id)>
                                    {{ $managementAccount->code }} {{ $managementAccount->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-label for="per_page" value="表示件数" class="font-semibold text-gray-800" />
                        <select
                            id="per_page"
                            name="per_page"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-32"
                        >
                            @foreach ($perPageOptions as $perPageOption)
                                <option value="{{ $perPageOption }}" @selected($selectedPerPage === $perPageOption)>
                                    {{ $perPageOption }}件
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-button type="submit" class="text-sm">表示</x-button>

                        @if ($selectedType !== null || $selectedYear !== null || $selectedMonth !== null || $selectedDepartmentId !== null || $selectedManagementAccountId !== null || $selectedPerPage !== $perPageOptions[0])
                            <a href="{{ route('amounts.index') }}" class="inline-flex items-center px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900">
                                クリア
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[64rem] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">年月</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">部門</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">予実管理科目</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">区分</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">金額</th>
                                <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-800">メモ</th>
                                <th scope="col" class="w-20 whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($amounts as $amount)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-base font-medium text-gray-900">{{ $amount->period->format('Y/m') }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-base text-gray-900">
                                        {{ $amount->department->code }} {{ $amount->department->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-base text-gray-900">
                                        {{ $amount->managementAccount->code }} {{ $amount->managementAccount->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-base text-gray-900">{{ \App\Models\MonthlyAmount::TYPES[$amount->type] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-base font-semibold text-gray-900">{{ number_format((float) $amount->amount, 2) }}</td>
                                    <td class="max-w-xs truncate px-4 py-4 text-base text-gray-900" title="{{ $amount->memo }}">{{ $amount->memo }}</td>
                                    <td class="w-20 whitespace-nowrap px-4 py-4 text-right text-base">
                                        <div class="flex justify-end">
                                            <x-dropdown align="right" width="48" :teleport="true">
                                                <x-slot name="trigger">
                                                    <x-icon-button label="操作メニューを開く" aria-haspopup="menu" x-bind:aria-expanded="open.toString()">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                            <circle cx="12" cy="5" r="1.5" />
                                                            <circle cx="12" cy="12" r="1.5" />
                                                            <circle cx="12" cy="19" r="1.5" />
                                                        </svg>
                                                    </x-icon-button>
                                                </x-slot>

                                                <x-slot name="content">
                                                    <div role="menu">
                                                        <x-dropdown-link href="{{ route('amounts.edit', $amount) }}" role="menuitem">
                                                            <span class="flex items-center gap-2">
                                                                <svg class="size-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487Z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.875 4.5M18 14.25v4.125A2.625 2.625 0 0 1 15.375 21H5.625A2.625 2.625 0 0 1 3 18.375V8.625A2.625 2.625 0 0 1 5.625 6H9.75" />
                                                                </svg>
                                                                <span>編集</span>
                                                            </span>
                                                        </x-dropdown-link>
                                                        <form method="POST" action="{{ route('amounts.destroy', $amount) }}" onsubmit="return confirm('この明細を削除しますか？')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <x-dropdown-button type="submit" role="menuitem">
                                                                <span class="flex items-center gap-2">
                                                                    <svg class="size-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.477c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                    </svg>
                                                                    <span>削除</span>
                                                                </span>
                                                            </x-dropdown-button>
                                                        </form>
                                                    </div>
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-base text-gray-700">予算・実績明細が登録されていません。</td>
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
