<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">仕訳帳</h2>
                <p class="mt-1 text-base text-gray-700">登録済みの複式仕訳を日付順に確認できます。</p>
            </div>
            <x-button href="{{ route('journal-entries.create') }}" class="text-sm">仕訳を登録</x-button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-base font-medium text-green-900">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-md bg-amber-50 p-4 text-base font-medium text-amber-900">{{ session('warning') }}</div>
            @endif

            <div class="bg-white p-3 shadow-sm sm:rounded-lg">
                <form
                    method="GET"
                    action="{{ route('journal-entries.index') }}"
                    class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end"
                    x-data="{
                        selectedYear: {{ Js::from($selectedYear === null ? '' : (string) $selectedYear) }},
                        selectedMonth: {{ Js::from($selectedMonth === null ? '' : (string) $selectedMonth) }},
                    }"
                >
                    <div>
                        <x-label for="year" value="対象年度" class="font-semibold text-gray-800" />
                        <select
                            id="year"
                            name="year"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-32"
                            x-model="selectedYear"
                            @change="if (selectedYear === '') selectedMonth = ''"
                        >
                            <option value="">すべて</option>
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}年度</option>
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
                            @foreach ($fiscalYearMonths as $month)
                                <option value="{{ $month }}" @selected($selectedMonth === $month)>{{ $month }}月</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-label for="sort_direction" value="並び順" class="font-semibold text-gray-800" />
                        <select
                            id="sort_direction"
                            name="sort_direction"
                            class="mt-1 block w-full rounded-md border-gray-400 py-2 text-base text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-40"
                        >
                            @foreach ($sortDirectionOptions as $sortDirection => $sortDirectionLabel)
                                <option value="{{ $sortDirection }}" @selected($selectedSortDirection === $sortDirection)>
                                    {{ $sortDirectionLabel }}
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

                        @if ($selectedYear !== $currentFiscalYear || $selectedMonth !== null || $selectedPerPage !== $perPageOptions[0] || $selectedSortDirection !== 'desc')
                            <a href="{{ route('journal-entries.index', ['reset_filters' => 1]) }}" class="inline-flex items-center px-3 py-2 text-base font-medium text-gray-700 hover:text-gray-900">
                                クリア
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">ID</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">仕訳日</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">摘要</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">借方合計</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">貸方合計</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">明細</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">証憑</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($journalEntries as $journalEntry)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-base">
                                        <a
                                            href="{{ route('journal-entries.show', $journalEntry) }}"
                                            class="font-semibold text-sky-700 hover:text-sky-900"
                                        >
                                            #{{ $journalEntry->id }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-base text-gray-900">{{ $journalEntry->entry_date->format('Y/m/d') }}</td>
                                    <td class="min-w-[16rem] px-4 py-4 text-base text-gray-900">{{ $journalEntry->description }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-base font-semibold text-gray-900">{{ number_format((float) $journalEntry->debitTotal(), 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-base font-semibold text-gray-900">{{ number_format((float) $journalEntry->creditTotal(), 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-base text-gray-800">{{ $journalEntry->lines_count }}行</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right text-base text-gray-800">{{ $journalEntry->documents_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-10 text-center text-base text-gray-700">仕訳が登録されていません。</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $journalEntries->links() }}
        </div>
    </div>
</x-app-layout>
