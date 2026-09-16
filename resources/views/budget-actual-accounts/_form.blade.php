<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-label for="code" value="予実管理科目コード" />
        <x-input
            id="code"
            name="code"
            type="text"
            class="mt-1 block w-full"
            :value="old('code', $budgetActualAccount?->code)"
            required
            autofocus
        />
        <x-input-error for="code" class="mt-2" />
    </div>

    <div>
        <x-label for="name" value="予実管理科目名" />
        <x-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $budgetActualAccount?->name)"
            required
        />
        <x-input-error for="name" class="mt-2" />
    </div>

    <div>
        <x-label for="account_type" value="予実管理科目区分" />
        <select id="account_type" name="account_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">選択してください</option>
            @foreach ($accountTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('account_type', $budgetActualAccount?->account_type) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error for="account_type" class="mt-2" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('budget-actual-accounts.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">
            キャンセル
        </a>
        <x-button>{{ $submitLabel }}</x-button>
    </div>
</form>
