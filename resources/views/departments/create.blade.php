<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <x-back-link href="{{ route('departments.index') }}">一覧へ戻る</x-back-link>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">部門を登録</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                @include('departments._form', [
                    'action' => route('departments.store'),
                    'method' => 'POST',
                    'department' => null,
                    'submitLabel' => '登録',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
