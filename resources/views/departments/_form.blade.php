<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-label for="code" value="部門コード" />
        <x-input
            id="code"
            name="code"
            type="text"
            class="mt-1 block w-full"
            :value="old('code', $department?->code)"
            required
            autofocus
        />
        <x-input-error for="code" class="mt-2" />
    </div>

    <div>
        <x-label for="name" value="部門名" />
        <x-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $department?->name)"
            required
        />
        <x-input-error for="name" class="mt-2" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('departments.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">
            キャンセル
        </a>
        <x-button>{{ $submitLabel }}</x-button>
    </div>
</form>
