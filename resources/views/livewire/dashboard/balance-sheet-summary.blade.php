<section aria-labelledby="balance-sheet-summary">
    <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <h2 id="balance-sheet-summary" class="text-xl font-semibold text-gray-900">B/Sサマリー</h2>
            <p class="mt-1 text-base text-gray-700">{{ $fiscalYear }}年度末（{{ str_replace('-', '/', $periodEnd) }}現在）</p>
        </div>

        <div>
            <x-label for="balance-sheet-fiscal-year" value="対象年度" class="font-semibold text-gray-800" />
            <select id="balance-sheet-fiscal-year" wire:model.live="fiscalYear" wire:loading.attr="disabled" wire:target="fiscalYear" class="mt-1 rounded-md border-gray-300 bg-white text-base font-medium text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($availableFiscalYears as $availableFiscalYear)
                    <option value="{{ $availableFiscalYear }}">{{ $availableFiscalYear }}年度</option>
                @endforeach
            </select>
        </div>
    </div>

    <div wire:loading.delay wire:target="fiscalYear" class="mb-3 text-sm font-semibold text-indigo-700" role="status">表示年度を切り替えています…</div>

    <div wire:loading.class="opacity-60" wire:target="fiscalYear" class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center shadow-sm transition-opacity">
        <p class="text-base font-semibold text-gray-700">表示できるデータはまだありません</p>
        <p class="mt-2 text-sm text-gray-600">期首残高、決算振替および帳簿締切の機能を整備後に表示します。</p>
    </div>
</section>
