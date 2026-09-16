<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <x-back-link href="{{ route('budget-actual-accounts.index') }}">一覧へ戻る</x-back-link>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">予実管理科目を編集</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                @include('budget-actual-accounts._form', [
                    'action' => route('budget-actual-accounts.update', $budgetActualAccount),
                    'method' => 'PUT',
                    'budgetActualAccount' => $budgetActualAccount,
                    'submitLabel' => '更新',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
