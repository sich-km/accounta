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
            <section aria-labelledby="registration-summary">
                <div class="mb-4">
                    <h1 id="registration-summary" class="text-xl font-semibold text-gray-900">登録状況</h1>
                    <p class="mt-1 text-sm text-gray-600">現在の組織に登録されているデータ件数です。</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <a href="{{ route('amounts.index') }}" class="rounded-xl border border-sky-100 bg-white p-5 shadow-sm transition hover:border-sky-200 hover:shadow-md">
                        <p class="text-sm font-medium text-gray-500">予算レコード</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold text-sky-600">{{ number_format($organization->budget_records_count) }}</span>
                            <span class="text-sm text-gray-500">件</span>
                        </p>
                    </a>

                    <a href="{{ route('amounts.index') }}" class="rounded-xl border border-cyan-100 bg-white p-5 shadow-sm transition hover:border-cyan-200 hover:shadow-md">
                        <p class="text-sm font-medium text-gray-500">実績レコード</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold text-cyan-600">{{ number_format($organization->actual_records_count) }}</span>
                            <span class="text-sm text-gray-500">件</span>
                        </p>
                    </a>

                    <a href="{{ route('departments.index') }}" class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md">
                        <p class="text-sm font-medium text-gray-500">有効な部門</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold text-blue-600">{{ number_format($organization->active_departments_count) }}</span>
                            <span class="text-sm text-gray-500">件</span>
                        </p>
                    </a>

                    <a href="{{ route('accounts.index') }}" class="rounded-xl border border-indigo-100 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                        <p class="text-sm font-medium text-gray-500">有効な勘定科目</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold text-indigo-600">{{ number_format($organization->active_accounts_count) }}</span>
                            <span class="text-sm text-gray-500">件</span>
                        </p>
                    </a>
                </div>
            </section>

            <section aria-labelledby="quick-actions">
                <h2 id="quick-actions" class="mb-4 text-xl font-semibold text-gray-900">メニュー</h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <a href="{{ route('amounts.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                        <h3 class="text-lg font-semibold text-gray-900">予算・実績</h3>
                        <p class="mt-2 text-sm text-gray-600">月次の予算・実績明細を登録します。</p>
                    </a>
                    <a href="{{ route('departments.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                        <h3 class="text-lg font-semibold text-gray-900">部門マスタ</h3>
                        <p class="mt-2 text-sm text-gray-600">入力に使用する部門を管理します。</p>
                    </a>
                    <a href="{{ route('accounts.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                        <h3 class="text-lg font-semibold text-gray-900">勘定科目マスタ</h3>
                        <p class="mt-2 text-sm text-gray-600">入力に使用する勘定科目を管理します。</p>
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('companies.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                            <h3 class="text-lg font-semibold text-gray-900">会社情報管理</h3>
                            <p class="mt-2 text-sm text-gray-600">会社情報の登録・編集・削除を行います。</p>
                        </a>
                    @else
                        <a href="{{ route('profile.show') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                            <h3 class="text-lg font-semibold text-gray-900">組織情報</h3>
                            <p class="mt-2 text-sm text-gray-600">所属している会社と組織を確認します。</p>
                        </a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
