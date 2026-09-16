<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    ダッシュボード
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $organization->company->name }} / {{ $organization->name }}
                </p>
            </div>

            <a
                href="{{ route('export.excel') }}"
                class="inline-flex items-center justify-center gap-2 rounded-md border border-transparent bg-sky-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-sky-700 focus:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 active:bg-sky-800"
            >
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.69L6.78 8.97a.75.75 0 00-1.06 1.06l3.75 3.75a.75.75 0 001.06 0l3.75-3.75a.75.75 0 10-1.06-1.06l-2.47 2.47V2.75z" />
                    <path d="M3.5 12.25a.75.75 0 00-1.5 0v2.5A2.25 2.25 0 004.25 17h11.5A2.25 2.25 0 0018 14.75v-2.5a.75.75 0 00-1.5 0v2.5a.75.75 0 01-.75.75H4.25a.75.75 0 01-.75-.75v-2.5z" />
                </svg>
                Excelへエクスポート
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section aria-labelledby="journal-summary">
                <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <h1 id="journal-summary" class="text-xl font-semibold text-gray-900">仕訳</h1>
                        <p class="mt-1 text-base text-gray-700">
                            {{ $profitAndLossSummary['fiscalYear'] }}年度
                            （{{ str_replace('-', '/', $profitAndLossSummary['periodStart']) }}〜{{ str_replace('-', '/', $profitAndLossSummary['periodEnd']) }}）の登録件数です。
                        </p>
                    </div>
                    <x-button href="{{ route('journal-entries.index', ['year' => $profitAndLossSummary['fiscalYear']]) }}" class="text-sm">仕訳帳を表示</x-button>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)]">
                    <a href="{{ route('journal-entries.index', ['year' => $profitAndLossSummary['fiscalYear']]) }}" class="rounded-xl border border-violet-200 bg-white p-5 shadow-sm transition hover:border-violet-300 hover:shadow-md">
                        <p class="text-sm font-semibold text-gray-700">現在年度の仕訳</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold tabular-nums text-violet-700">{{ number_format($organization->current_fiscal_year_journal_entries_count) }}</span>
                            <span class="text-sm text-gray-600">件</span>
                        </p>
                        <p class="mt-4 text-sm text-gray-600">登録された仕訳から右の収益・費用を集計しています。</p>
                    </a>

                    <div id="journal-profit-chart" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-baseline">
                            <h2 class="text-base font-semibold text-gray-900">収益・費用比較</h2>
                            <p class="text-sm text-gray-600">収益・費用と、その差額を連続して表示</p>
                        </div>

                        <div
                            role="img"
                            aria-label="収益 {{ number_format((float) $profitAndLossChart['revenue'], 2) }}円、費用 {{ number_format((float) $profitAndLossChart['expenses'], 2) }}円、税引前損益 {{ number_format((float) $profitAndLossChart['profitOrLoss'], 2) }}円"
                            class="mt-4"
                        >
                            <div class="grid grid-cols-1 gap-2 text-sm font-semibold sm:grid-cols-3">
                                <p class="rounded-md bg-sky-50 px-3 py-2 text-sky-950">
                                    収益 <span class="tabular-nums">{{ number_format((float) $profitAndLossChart['revenue'], 2) }}円</span>
                                </p>
                                <p class="rounded-md bg-red-50 px-3 py-2 text-red-950">
                                    費用 <span class="tabular-nums">{{ number_format((float) $profitAndLossChart['expenses'], 2) }}円</span>
                                </p>
                                <p class="rounded-md bg-amber-50 px-3 py-2 text-amber-950">
                                    {{ (float) $profitAndLossChart['profitOrLoss'] >= 0 ? '利益' : '損失' }}
                                    <span class="tabular-nums">{{ number_format(abs((float) $profitAndLossChart['profitOrLoss']), 2) }}円</span>
                                </p>
                            </div>

                            <div class="mx-auto mt-4 grid h-56 max-w-md grid-cols-2 items-end gap-0 border-b-2 border-gray-400 px-4 sm:px-10">
                                <div class="flex h-full flex-col justify-end">
                                    @if ((float) $profitAndLossChart['profitOrLoss'] < 0)
                                        <div
                                            data-chart-segment="loss"
                                            class="w-full shrink-0 rounded-tl-md border border-amber-300 bg-amber-100"
                                            style="height: calc({{ $profitAndLossChart['expenseHeightPercentage'] }}% - {{ $profitAndLossChart['revenueHeightPercentage'] }}%)"
                                            aria-hidden="true"
                                        ></div>
                                    @endif
                                    <div
                                        data-chart-segment="revenue"
                                        class="w-full shrink-0 {{ (float) $profitAndLossChart['profitOrLoss'] >= 0 ? 'rounded-tl-md' : '' }} border border-sky-300 bg-sky-200"
                                        style="height: {{ $profitAndLossChart['revenueHeightPercentage'] }}%"
                                        aria-hidden="true"
                                    ></div>
                                </div>
                                <div class="flex h-full flex-col justify-end">
                                    @if ((float) $profitAndLossChart['profitOrLoss'] > 0)
                                        <div
                                            data-chart-segment="profit"
                                            class="w-full shrink-0 rounded-tr-md border border-amber-300 bg-amber-100"
                                            style="height: calc({{ $profitAndLossChart['revenueHeightPercentage'] }}% - {{ $profitAndLossChart['expenseHeightPercentage'] }}%)"
                                            aria-hidden="true"
                                        ></div>
                                    @endif
                                    <div
                                        data-chart-segment="expenses"
                                        class="w-full shrink-0 {{ (float) $profitAndLossChart['profitOrLoss'] <= 0 ? 'rounded-tr-md' : '' }} border border-red-300 bg-red-200"
                                        style="height: {{ $profitAndLossChart['expenseHeightPercentage'] }}%"
                                        aria-hidden="true"
                                    ></div>
                                </div>
                            </div>
                            <div class="mx-auto grid max-w-md grid-cols-2 gap-0 px-4 pt-2 text-center text-sm font-semibold text-gray-800 sm:px-10">
                                <p>収益</p>
                                <p>費用＋{{ (float) $profitAndLossChart['profitOrLoss'] >= 0 ? '利益' : '損失' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center">
                            <p class="text-sm font-semibold text-gray-700">差額（税引前損益）</p>
                            <p class="text-xl font-semibold tabular-nums {{ (float) $profitAndLossChart['profitOrLoss'] >= 0 ? 'text-amber-900' : 'text-red-800' }}">
                                {{ number_format((float) $profitAndLossChart['profitOrLoss'], 2) }}円
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section aria-labelledby="profit-and-loss-summary">
                <div class="mb-4">
                    <h2 id="profit-and-loss-summary" class="text-xl font-semibold text-gray-900">P/Lサマリー</h2>
                    <p class="mt-1 text-base text-gray-700">
                        {{ $profitAndLossSummary['fiscalYear'] }}年度
                        （{{ str_replace('-', '/', $profitAndLossSummary['periodStart']) }}〜{{ str_replace('-', '/', $profitAndLossSummary['periodEnd']) }}）の仕訳帳から集計しています。
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-xl border border-sky-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700">収益合計</p>
                        <p class="mt-3 text-2xl font-semibold tabular-nums text-sky-700">
                            {{ number_format((float) $profitAndLossSummary['totalRevenue'], 2) }}
                            <span class="text-sm font-medium text-gray-600">円</span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700">費用合計（税引前）</p>
                        <p class="mt-3 text-2xl font-semibold tabular-nums text-amber-700">
                            {{ number_format((float) $profitAndLossSummary['expensesBeforeTax'], 2) }}
                            <span class="text-sm font-medium text-gray-600">円</span>
                        </p>
                    </div>

                    @foreach ([
                        ['label' => '営業利益', 'amount' => $profitAndLossSummary['operatingProfit']],
                        ['label' => '経常利益', 'amount' => $profitAndLossSummary['ordinaryProfit']],
                        ['label' => '税引前当期純利益', 'amount' => $profitAndLossSummary['profitBeforeTax']],
                    ] as $profit)
                        <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-gray-700">{{ $profit['label'] }}</p>
                            <p class="mt-3 text-2xl font-semibold tabular-nums {{ (float) $profit['amount'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ number_format((float) $profit['amount'], 2) }}
                                <span class="text-sm font-medium text-gray-600">円</span>
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-3">
                    <p class="text-base text-gray-800">
                        営業収益
                        <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['operatingRevenue'], 2) }}円</span>
                        ／営業費用
                        <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['operatingExpenses'], 2) }}円</span>
                    </p>
                    <p class="text-base text-gray-800">
                        営業外収益
                        <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['nonOperatingRevenue'], 2) }}円</span>
                        ／営業外費用
                        <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['nonOperatingExpenses'], 2) }}円</span>
                    </p>
                    <p class="text-base text-gray-800">
                        特別利益
                        <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['extraordinaryIncome'], 2) }}円</span>
                        ／特別損失
                        <span class="font-semibold tabular-nums">{{ number_format((float) $profitAndLossSummary['extraordinaryLoss'], 2) }}円</span>
                    </p>
                </div>

                <p class="mt-3 text-sm text-gray-700">
                    現時点では標準勘定コードに基づく暫定集計です。法人税等は費用合計と税引前当期純利益から除外しています。
                </p>

                @if ($profitAndLossSummary['unclassifiedAccountsCount'] > 0)
                    <p class="mt-2 rounded-md bg-amber-50 p-3 text-sm font-medium text-amber-900">
                        損益区分を判定できない勘定科目が{{ $profitAndLossSummary['unclassifiedAccountsCount'] }}件あります。
                    </p>
                @endif
            </section>

            <section aria-labelledby="balance-sheet-summary">
                <div class="mb-4">
                    <h2 id="balance-sheet-summary" class="text-xl font-semibold text-gray-900">B/Sサマリー</h2>
                    <p class="mt-1 text-base text-gray-700">貸借対照表の集計機能は今後実装します。</p>
                </div>

                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center shadow-sm">
                    <p class="text-base font-semibold text-gray-700">表示できるデータはまだありません</p>
                    <p class="mt-2 text-sm text-gray-600">期首残高、決算振替および帳簿締切の機能を整備後に表示します。</p>
                </div>
            </section>

            <section aria-labelledby="fixed-asset-summary">
                <div class="mb-4">
                    <h2 id="fixed-asset-summary" class="text-xl font-semibold text-gray-900">固定資産</h2>
                    <p class="mt-1 text-sm text-gray-600">保有中の固定資産だけを集計しています。</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <a href="{{ route('fixed-assets.index') }}" class="rounded-xl border border-emerald-100 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
                        <p class="text-sm font-medium text-gray-500">保有中固定資産</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold text-emerald-600">{{ number_format($fixedAssetSummary['count']) }}</span>
                            <span class="text-sm text-gray-500">件</span>
                        </p>
                    </a>

                    <a href="{{ route('fixed-assets.index') }}" class="rounded-xl border border-teal-100 bg-white p-5 shadow-sm transition hover:border-teal-200 hover:shadow-md">
                        <p class="text-sm font-medium text-gray-500">保有中資産の取得価額</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-semibold text-teal-600">{{ number_format((float) $fixedAssetSummary['acquisitionCost'], 2) }}</span>
                            <span class="text-sm text-gray-500">円</span>
                        </p>
                    </a>

                    <a href="{{ route('fixed-assets.index') }}" class="rounded-xl border border-cyan-100 bg-white p-5 shadow-sm transition hover:border-cyan-200 hover:shadow-md">
                        <p class="text-sm font-medium text-gray-500">保有中資産の帳簿価額</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-semibold text-cyan-600">{{ number_format((float) $fixedAssetSummary['bookValue'], 2) }}</span>
                            <span class="text-sm text-gray-500">円</span>
                        </p>
                    </a>
                </div>
            </section>

            <section aria-labelledby="budget-actual-summary">
                <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <h2 id="budget-actual-summary" class="text-xl font-semibold text-gray-900">予実管理</h2>
                        <p class="mt-1 text-base text-gray-700">
                            {{ $profitAndLossSummary['fiscalYear'] }}年度の予算・実績レコード件数です。
                        </p>
                    </div>
                    <x-button href="{{ route('amounts.index') }}" class="text-sm">予実管理を表示</x-button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <a href="{{ route('amounts.index', ['type' => 'budget']) }}" class="rounded-xl border border-sky-200 bg-white p-5 shadow-sm transition hover:border-sky-300 hover:shadow-md">
                        <p class="text-sm font-semibold text-gray-700">現在年度の予算レコード</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold tabular-nums text-sky-700">{{ number_format($organization->current_fiscal_year_budget_records_count) }}</span>
                            <span class="text-sm text-gray-600">件</span>
                        </p>
                    </a>

                    <a href="{{ route('amounts.index', ['type' => 'actual']) }}" class="rounded-xl border border-cyan-200 bg-white p-5 shadow-sm transition hover:border-cyan-300 hover:shadow-md">
                        <p class="text-sm font-semibold text-gray-700">現在年度の実績レコード</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold tabular-nums text-cyan-700">{{ number_format($organization->current_fiscal_year_actual_records_count) }}</span>
                            <span class="text-sm text-gray-600">件</span>
                        </p>
                    </a>
                </div>

                <div id="monthly-budget-actual-chart" class="mt-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-baseline">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ $monthlyBudgetActualSummary['monthLabel'] }}の予実損益</h3>
                            <p class="mt-1 text-sm text-gray-600">予算・実績それぞれの収益から費用を差し引いています。</p>
                        </div>
                        <p class="text-sm text-gray-600">損益の絶対額が大きい方を100%として表示</p>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,24rem)]">
                        <div
                            role="img"
                            aria-label="予算損益 {{ number_format((float) $monthlyBudgetActualSummary['budget']['profitOrLoss'], 2) }}円、実績損益 {{ number_format((float) $monthlyBudgetActualSummary['actual']['profitOrLoss'], 2) }}円"
                        >
                            <div class="grid h-64 grid-cols-2 items-end gap-6 border-b-2 border-gray-300 px-4 sm:gap-10 sm:px-12">
                                @foreach ([
                                    ['key' => 'budget', 'label' => '予算', 'positiveColor' => 'bg-sky-500'],
                                    ['key' => 'actual', 'label' => '実績', 'positiveColor' => 'bg-cyan-500'],
                                ] as $bar)
                                    @php($barSummary = $monthlyBudgetActualSummary[$bar['key']])
                                    <div class="flex h-full flex-col gap-2">
                                        <p class="text-center text-sm font-semibold tabular-nums text-gray-800">
                                            @if ($barSummary['records'] > 0)
                                                {{ number_format((float) $barSummary['profitOrLoss'], 2) }}円
                                            @else
                                                未登録
                                            @endif
                                        </p>
                                        <div class="flex min-h-0 flex-1 items-end">
                                            <div
                                                class="mx-auto w-full max-w-40 rounded-t-md {{ (float) $barSummary['profitOrLoss'] >= 0 ? $bar['positiveColor'] : 'bg-red-500' }}"
                                                style="height: {{ $barSummary['heightPercentage'] }}%"
                                                aria-hidden="true"
                                            ></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="grid grid-cols-2 gap-6 px-4 pt-2 text-center text-sm font-semibold text-gray-800 sm:gap-10 sm:px-12">
                                <p>予算損益</p>
                                <p>実績損益</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @foreach ([
                                ['key' => 'budget', 'label' => '今月予算'],
                                ['key' => 'actual', 'label' => '今月実績'],
                            ] as $summary)
                                @php($amountSummary = $monthlyBudgetActualSummary[$summary['key']])
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-gray-800">{{ $summary['label'] }}</p>
                                        <span class="text-xs font-medium text-gray-600">{{ number_format($amountSummary['records']) }}件</span>
                                    </div>
                                    @if ($amountSummary['records'] > 0)
                                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                            <dt class="text-gray-600">収益</dt>
                                            <dd class="text-right font-semibold tabular-nums text-sky-700">{{ number_format((float) $amountSummary['revenue'], 2) }}円</dd>
                                            <dt class="text-gray-600">費用</dt>
                                            <dd class="text-right font-semibold tabular-nums text-amber-700">{{ number_format((float) $amountSummary['expenses'], 2) }}円</dd>
                                            <dt class="text-gray-700">損益</dt>
                                            <dd class="text-right font-semibold tabular-nums {{ (float) $amountSummary['profitOrLoss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                                {{ number_format((float) $amountSummary['profitOrLoss'], 2) }}円
                                            </dd>
                                        </dl>
                                    @else
                                        <p class="mt-3 text-sm font-medium text-gray-600">{{ $summary['label'] }}は未登録です。</p>
                                    @endif
                                </div>
                            @endforeach

                            <div class="rounded-lg bg-gray-50 px-4 py-3">
                                <p class="text-sm font-semibold text-gray-700">予算差異（実績損益−予算損益）</p>
                                @if ($monthlyBudgetActualSummary['variance'] !== null)
                                    <p class="mt-1 text-xl font-semibold tabular-nums {{ (float) $monthlyBudgetActualSummary['variance'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ number_format((float) $monthlyBudgetActualSummary['variance'], 2) }}円
                                    </p>
                                @else
                                    <p class="mt-1 text-sm text-gray-600">予算と実績が揃うと表示します。</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="management-summary" aria-labelledby="management-summary-heading">
                <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <h2 id="management-summary-heading" class="text-xl font-semibold text-gray-900">管理</h2>
                        <p class="mt-1 text-base text-gray-700">現在利用できる組織・会計マスタの件数です。</p>
                    </div>
                    @if (auth()->user()->canManageMasters())
                        <x-button href="{{ route('management.index') }}" class="text-sm">管理画面を表示</x-button>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['label' => '有効な部門', 'count' => $organization->active_departments_count, 'color' => 'text-blue-700'],
                        ['label' => '有効な予実管理科目', 'count' => $organization->active_management_accounts_count, 'color' => 'text-indigo-700'],
                        ['label' => '有効な仕訳用勘定科目', 'count' => $organization->active_ledger_accounts_count, 'color' => 'text-fuchsia-700'],
                    ] as $masterSummary)
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-sm font-semibold text-gray-700">{{ $masterSummary['label'] }}</p>
                            <p class="mt-3 flex items-baseline gap-2">
                                <span class="text-3xl font-semibold tabular-nums {{ $masterSummary['color'] }}">{{ number_format($masterSummary['count']) }}</span>
                                <span class="text-sm text-gray-600">件</span>
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="accounting-study-summary">
                <div class="mb-4">
                    <h2 id="accounting-study-summary" class="text-xl font-semibold text-gray-900">会計学習</h2>
                    <p class="mt-1 text-base text-gray-700">財務諸表論の理論学習機能は今後実装します。</p>
                </div>

                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center shadow-sm">
                    <p class="text-base font-semibold text-gray-700">学習データはまだありません</p>
                    <p class="mt-2 text-sm text-gray-600">問題生成、定着度判定および学習進捗管理の準備中です。</p>
                </div>
            </section>

            <section aria-labelledby="quick-actions">
                <h2 id="quick-actions" class="mb-4 text-xl font-semibold text-gray-900">メニュー</h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <a href="{{ route('journal-entries.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                        <h3 class="text-lg font-semibold text-gray-900">仕訳帳</h3>
                        <p class="mt-2 text-sm text-gray-600">複合仕訳の登録と証憑の添付を行います。</p>
                    </a>
                    <a href="{{ route('fixed-assets.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                        <h3 class="text-lg font-semibold text-gray-900">固定資産管理台帳</h3>
                        <p class="mt-2 text-sm text-gray-600">固定資産と減価償却の現在値を管理します。</p>
                    </a>
                    <a href="{{ route('amounts.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                        <h3 class="text-lg font-semibold text-gray-900">予算・実績</h3>
                        <p class="mt-2 text-sm text-gray-600">月次の予算・実績明細を登録します。</p>
                    </a>
                    @unless (auth()->user()->isAdmin())
                        <a href="{{ route('profile.show') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                            <h3 class="text-lg font-semibold text-gray-900">組織情報</h3>
                            <p class="mt-2 text-sm text-gray-600">所属している会社と組織を確認します。</p>
                        </a>
                    @endunless
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
