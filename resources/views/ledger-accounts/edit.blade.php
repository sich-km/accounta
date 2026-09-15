<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <x-back-link href="{{ route('ledger-accounts.index') }}">一覧へ戻る</x-back-link>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">仕訳用勘定科目を編集</h2>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                @include('ledger-accounts._form', ['action' => route('ledger-accounts.update', $ledgerAccount), 'method' => 'PUT', 'ledgerAccount' => $ledgerAccount, 'submitLabel' => '更新'])
            </div>
        </div>
    </div>
</x-app-layout>
