<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold leading-tight text-gray-800">仕訳を登録</h2></x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                @include('journal-entries._form', ['action' => route('journal-entries.store'), 'method' => 'POST', 'journalEntry' => null, 'submitLabel' => '登録', 'cancelUrl' => route('journal-entries.index')])
            </div>
        </div>
    </div>
</x-app-layout>
