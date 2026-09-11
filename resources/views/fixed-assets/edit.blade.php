<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">固定資産を編集</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                @include('fixed-assets._form', [
                    'action' => route('fixed-assets.update', $fixedAsset),
                    'method' => 'PUT',
                    'submitLabel' => '更新',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
