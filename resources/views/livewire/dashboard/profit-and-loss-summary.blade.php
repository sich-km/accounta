<section aria-labelledby="profit-and-loss-summary">
    <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <h2 id="profit-and-loss-summary" class="text-xl font-semibold text-gray-900">P/Lサマリー</h2>
            <p class="mt-1 text-base text-gray-700">
                {{ $profitAndLossSummary['fiscalYear'] }}年度
                （{{ str_replace('-', '/', $profitAndLossSummary['periodStart']) }}〜{{ str_replace('-', '/', $profitAndLossSummary['periodEnd']) }}）の仕訳帳から集計しています。
            </p>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <x-label for="profit-and-loss-fiscal-year" value="対象年度" class="font-semibold text-gray-800" />
                <select id="profit-and-loss-fiscal-year" wire:model.live="fiscalYear" wire:loading.attr="disabled" wire:target="fiscalYear" class="mt-1 rounded-md border-gray-300 bg-white text-base font-medium text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($availableFiscalYears as $availableFiscalYear)
                        <option value="{{ $availableFiscalYear }}">{{ $availableFiscalYear }}年度</option>
                    @endforeach
                </select>
            </div>
            <x-button href="{{ route('journal-entries.index', ['year' => $fiscalYear]) }}" class="text-sm">仕訳帳を表示</x-button>
        </div>
    </div>

    <div wire:loading.delay wire:target="fiscalYear" class="mb-3 text-sm font-semibold text-indigo-700" role="status">集計中です…</div>

    <div wire:loading.class="opacity-60" wire:target="fiscalYear" class="transition-opacity">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)]">
            <a href="{{ route('journal-entries.index', ['year' => $fiscalYear]) }}" class="rounded-xl border border-violet-200 bg-white p-5 shadow-sm transition hover:border-violet-300 hover:shadow-md">
                <p class="text-sm font-semibold text-gray-700">集計対象仕訳</p>
                <p class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tabular-nums text-violet-700">{{ number_format($journalEntriesCount) }}</span>
                    <span class="text-sm text-gray-600">件</span>
                </p>
                <p class="mt-4 text-sm text-gray-600">選択年度の仕訳から収益・費用・利益を集計しています。</p>
            </a>

            <div id="journal-profit-chart" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-baseline">
                    <h3 class="text-base font-semibold text-gray-900">収益・費用比較</h3>
                    <p class="text-sm text-gray-600">収益・費用と、その差額を連続して表示</p>
                </div>

                <div role="img" aria-label="収益 {{ number_format((float) $profitAndLossChart['revenue'], 2) }}円、費用 {{ number_format((float) $profitAndLossChart['expenses'], 2) }}円、税引前損益 {{ number_format((float) $profitAndLossChart['profitOrLoss'], 2) }}円" class="mt-4">
                    <div class="grid grid-cols-1 gap-2 text-sm font-semibold sm:grid-cols-3">
                        <p class="rounded-md bg-sky-50 px-3 py-2 text-sky-950">収益 <span class="tabular-nums">{{ number_format((float) $profitAndLossChart['revenue'], 2) }}円</span></p>
                        <p class="rounded-md bg-red-50 px-3 py-2 text-red-950">費用 <span class="tabular-nums">{{ number_format((float) $profitAndLossChart['expenses'], 2) }}円</span></p>
                        <p class="rounded-md bg-amber-50 px-3 py-2 text-amber-950">
                            {{ (float) $profitAndLossChart['profitOrLoss'] >= 0 ? '利益' : '損失' }}
                            <span class="tabular-nums">{{ number_format(abs((float) $profitAndLossChart['profitOrLoss']), 2) }}円</span>
                        </p>
                    </div>

                    <div class="mx-auto mt-4 grid h-56 max-w-md grid-cols-2 items-end gap-0 border-b-2 border-gray-400 px-4 sm:px-10">
                        <div class="flex h-full flex-col justify-end">
                            @if ((float) $profitAndLossChart['profitOrLoss'] < 0)
                                <div data-chart-segment="loss" class="w-full shrink-0 rounded-tl-md border border-amber-300 bg-amber-100" style="height: calc({{ $profitAndLossChart['expenseHeightPercentage'] }}% - {{ $profitAndLossChart['revenueHeightPercentage'] }}%)" aria-hidden="true"></div>
                            @endif
                            <div data-chart-segment="revenue" class="w-full shrink-0 {{ (float) $profitAndLossChart['profitOrLoss'] >= 0 ? 'rounded-tl-md' : '' }} border border-sky-300 bg-sky-200" style="height: {{ $profitAndLossChart['revenueHeightPercentage'] }}%" aria-hidden="true"></div>
                        </div>
                        <div class="flex h-full flex-col justify-end">
                            <div data-chart-segment="expenses" class="w-full shrink-0 rounded-tr-md border border-red-300 bg-red-200" style="height: {{ $profitAndLossChart['expenseHeightPercentage'] }}%" aria-hidden="true"></div>
                            @if ((float) $profitAndLossChart['profitOrLoss'] > 0)
                                <div data-chart-segment="profit" class="w-full shrink-0 border border-amber-300 bg-amber-100" style="height: calc({{ $profitAndLossChart['revenueHeightPercentage'] }}% - {{ $profitAndLossChart['expenseHeightPercentage'] }}%)" aria-hidden="true"></div>
                            @endif
                        </div>
                    </div>
                    <div class="mx-auto grid max-w-md grid-cols-2 gap-0 px-4 pt-2 text-center text-sm font-semibold text-gray-800 sm:px-10">
                        <p>収益</p>
                        <p>費用＋{{ (float) $profitAndLossChart['profitOrLoss'] >= 0 ? '利益' : '損失' }}</p>
                    </div>
                </div>

                <div class="mt-5 flex flex-col justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center">
                    <p class="text-sm font-semibold text-gray-700">差額（税引前損益）</p>
                    <p class="text-xl font-semibold tabular-nums {{ (float) $profitAndLossChart['profitOrLoss'] >= 0 ? 'text-amber-900' : 'text-red-800' }}">{{ number_format((float) $profitAndLossChart['profitOrLoss'], 2) }}円</p>
                </div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-sky-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-gray-700">収益合計</p>
                <p class="mt-3 text-2xl font-semibold tabular-nums text-sky-700">{{ number_format((float) $profitAndLossSummary['totalRevenue'], 2) }} <span class="text-sm font-medium text-gray-600">円</span></p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-gray-700">費用合計（税引前）</p>
                <p class="mt-3 text-2xl font-semibold tabular-nums text-amber-700">{{ number_format((float) $profitAndLossSummary['expensesBeforeTax'], 2) }} <span class="text-sm font-medium text-gray-600">円</span></p>
            </div>
            @foreach ([
                ['label' => '営業利益', 'amount' => $profitAndLossSummary['operatingProfit']],
                ['label' => '経常利益', 'amount' => $profitAndLossSummary['ordinaryProfit']],
                ['label' => '税引前当期純利益', 'amount' => $profitAndLossSummary['profitBeforeTax']],
            ] as $profit)
                <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-gray-700">{{ $profit['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold tabular-nums {{ (float) $profit['amount'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $profit['amount'], 2) }} <span class="text-sm font-medium text-gray-600">円</span></p>
                </div>
            @endforeach
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-3">
            <p class="text-base text-gray-800">営業収益 <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['operatingRevenue'], 2) }}円</span> ／営業費用 <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['operatingExpenses'], 2) }}円</span></p>
            <p class="text-base text-gray-800">営業外収益 <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['nonOperatingRevenue'], 2) }}円</span> ／営業外費用 <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['nonOperatingExpenses'], 2) }}円</span></p>
            <p class="text-base text-gray-800">特別利益 <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['extraordinaryIncome'], 2) }}円</span> ／特別損失 <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['extraordinaryLoss'], 2) }}円</span></p>
        </div>

        <p class="mt-3 text-sm text-gray-700">現時点では標準勘定コードに基づく暫定集計です。法人税等は費用合計と税引前当期純利益から除外しています。</p>

        @if ($profitAndLossSummary['unclassifiedAccountsCount'] > 0)
            <p class="mt-2 rounded-md bg-amber-50 p-3 text-sm font-medium text-amber-900">損益区分を判定できない勘定科目が{{ $profitAndLossSummary['unclassifiedAccountsCount'] }}件あります。</p>
        @endif
    </div>
</section>
