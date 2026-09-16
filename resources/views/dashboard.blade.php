<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ダッシュボード</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $organization->company->name }} / {{ $organization->name }}</p>
            </div>

            <a href="{{ route('export.excel') }}" class="inline-flex items-center justify-center gap-2 rounded-md border border-transparent bg-sky-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-sky-700 focus:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 active:bg-sky-800">
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
            <livewire:dashboard.profit-and-loss-summary />

            <livewire:dashboard.balance-sheet-summary />

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

            <livewire:dashboard.budget-actual-summary />

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
