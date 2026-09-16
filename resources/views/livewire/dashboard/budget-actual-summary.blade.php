<section aria-labelledby="budget-actual-summary">
    <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <h2 id="budget-actual-summary" class="text-xl font-semibold text-gray-900">予実管理</h2>
            <p class="mt-1 text-base text-gray-700">
                {{ $fiscalYear }}年度
                （{{ str_replace('-', '/', $budgetActualSummary['periodStart']) }}〜{{ str_replace('-', '/', $budgetActualSummary['periodEnd']) }}）の年間集計です。
            </p>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <x-label for="budget-actual-fiscal-year" value="対象年度" class="font-semibold text-gray-800" />
                <select id="budget-actual-fiscal-year" wire:model.live="fiscalYear" wire:loading.attr="disabled" wire:target="fiscalYear" class="mt-1 rounded-md border-gray-300 bg-white text-base font-medium text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($availableFiscalYears as $availableFiscalYear)
                        <option value="{{ $availableFiscalYear }}">{{ $availableFiscalYear }}年度</option>
                    @endforeach
                </select>
            </div>
            <x-button href="{{ route('amounts.index') }}" class="text-sm">予実管理を表示</x-button>
        </div>
    </div>

    <div wire:loading.delay wire:target="fiscalYear" class="mb-3 text-sm font-semibold text-indigo-700" role="status">集計中です…</div>

    <div wire:loading.class="opacity-60" wire:target="fiscalYear" class="transition-opacity">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <a href="{{ route('amounts.index', ['type' => 'budget']) }}" class="rounded-xl border border-sky-200 bg-white p-5 shadow-sm transition hover:border-sky-300 hover:shadow-md">
                <p class="text-sm font-semibold text-gray-700">選択年度の予算レコード</p>
                <p class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tabular-nums text-sky-700">{{ number_format($budgetActualSummary['budget']['records']) }}</span>
                    <span class="text-sm text-gray-600">件</span>
                </p>
            </a>

            <a href="{{ route('amounts.index', ['type' => 'actual']) }}" class="rounded-xl border border-cyan-200 bg-white p-5 shadow-sm transition hover:border-cyan-300 hover:shadow-md">
                <p class="text-sm font-semibold text-gray-700">選択年度の実績レコード</p>
                <p class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tabular-nums text-cyan-700">{{ number_format($budgetActualSummary['actual']['records']) }}</span>
                    <span class="text-sm text-gray-600">件</span>
                </p>
            </a>
        </div>

        <div id="annual-budget-actual-chart" class="mt-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-1 sm:flex-row sm:items-baseline">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ $fiscalYear }}年度の予実損益</h3>
                    <p class="mt-1 text-sm text-gray-600">予算・実績それぞれの年間収益から年間費用を差し引いています。</p>
                </div>
                <p class="text-sm text-gray-600">損益の絶対額が大きい方を100%として表示</p>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,24rem)]">
                <div role="img" aria-label="予算損益 {{ number_format((float) $budgetActualSummary['budget']['profitOrLoss'], 2) }}円、実績損益 {{ number_format((float) $budgetActualSummary['actual']['profitOrLoss'], 2) }}円">
                    <div class="grid h-64 grid-cols-2 items-end gap-6 border-b-2 border-gray-300 px-4 sm:gap-10 sm:px-12">
                        @foreach ([
                            ['key' => 'budget', 'positiveColor' => 'bg-sky-500'],
                            ['key' => 'actual', 'positiveColor' => 'bg-cyan-500'],
                        ] as $bar)
                            @php($barSummary = $budgetActualSummary[$bar['key']])
                            <div class="flex h-full flex-col gap-2">
                                <p class="text-center text-sm font-semibold tabular-nums text-gray-800">
                                    @if ($barSummary['records'] > 0)
                                        {{ number_format((float) $barSummary['profitOrLoss'], 2) }}円
                                    @else
                                        未登録
                                    @endif
                                </p>
                                <div class="flex min-h-0 flex-1 items-end">
                                    <div class="mx-auto w-full max-w-40 rounded-t-md {{ (float) $barSummary['profitOrLoss'] >= 0 ? $bar['positiveColor'] : 'bg-red-500' }}" style="height: {{ $barSummary['heightPercentage'] }}%" aria-hidden="true"></div>
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
                        ['key' => 'budget', 'label' => '年間予算'],
                        ['key' => 'actual', 'label' => '年間実績'],
                    ] as $summary)
                        @php($amountSummary = $budgetActualSummary[$summary['key']])
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
                                    <dd class="text-right font-semibold tabular-nums {{ (float) $amountSummary['profitOrLoss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $amountSummary['profitOrLoss'], 2) }}円</dd>
                                </dl>
                            @else
                                <p class="mt-3 text-sm font-medium text-gray-600">{{ $summary['label'] }}は未登録です。</p>
                            @endif
                        </div>
                    @endforeach

                    <div class="rounded-lg bg-gray-50 px-4 py-3">
                        <p class="text-sm font-semibold text-gray-700">予算差異（実績損益−予算損益）</p>
                        @if ($budgetActualSummary['variance'] !== null)
                            <p class="mt-1 text-xl font-semibold tabular-nums {{ (float) $budgetActualSummary['variance'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $budgetActualSummary['variance'], 2) }}円</p>
                        @else
                            <p class="mt-1 text-sm text-gray-600">予算と実績が揃うと表示します。</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
