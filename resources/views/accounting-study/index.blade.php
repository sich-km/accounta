<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">会計学習</h2>
            <p class="mt-1 text-sm text-gray-600">財務諸表論の理論学習状況を確認します。</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section aria-labelledby="study-summary-heading">
                <div class="mb-4">
                    <h2 id="study-summary-heading" class="text-xl font-semibold text-gray-900">学習サマリー</h2>
                    <p class="mt-1 text-base text-gray-700">現在の問題登録数と学習状況です。</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700">登録問題</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold tabular-nums text-blue-700">0</span>
                            <span class="text-sm text-gray-600">問</span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700">今日の復習対象</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold tabular-nums text-amber-700">0</span>
                            <span class="text-sm text-gray-600">問</span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-gray-700">学習済み</p>
                        <p class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl font-semibold tabular-nums text-emerald-700">0</span>
                            <span class="text-sm text-gray-600">問</span>
                        </p>
                    </div>
                </div>
            </section>

            <section aria-labelledby="study-status-heading">
                <div class="mb-4">
                    <h2 id="study-status-heading" class="text-xl font-semibold text-gray-900">学習状況</h2>
                    <p class="mt-1 text-base text-gray-700">問題の登録後、定着度や復習予定をここに表示します。</p>
                </div>

                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center shadow-sm">
                    <p class="text-base font-semibold text-gray-800">学習データはまだありません</p>
                    <p class="mt-2 text-sm text-gray-600">問題生成、定着度判定および学習進捗管理は今後実装します。</p>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
