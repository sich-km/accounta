<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">勘定科目を編集</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                @include('accounts._form', [
                    'action' => route('accounts.update', $account),
                    'method' => 'PUT',
                    'account' => $account,
                    'submitLabel' => '更新',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
