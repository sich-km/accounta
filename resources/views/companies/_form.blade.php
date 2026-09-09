<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-label for="code" value="企業コード" />
        <x-input
            id="code"
            name="code"
            type="text"
            class="mt-1 block w-full"
            :value="old('code', $company?->code)"
            required
            autofocus
        />
        <p class="mt-2 text-sm text-gray-500">将来、ログイン時の会社識別に使用します。</p>
        <x-input-error for="code" class="mt-2" />
    </div>

    <div>
        <x-label for="name" value="会社名" />
        <x-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $company?->name)"
            required
        />
        <x-input-error for="name" class="mt-2" />
    </div>

    <div>
        <x-label for="fiscal_year_start_month" value="会計年度開始月" />
        <select
            id="fiscal_year_start_month"
            name="fiscal_year_start_month"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >
            @foreach (range(1, 12) as $month)
                <option value="{{ $month }}" @selected((int) old('fiscal_year_start_month', $company?->fiscal_year_start_month ?? 1) === $month)>
                    {{ $month }}月
                </option>
            @endforeach
        </select>
        <x-input-error for="fiscal_year_start_month" class="mt-2" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('companies.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">
            キャンセル
        </a>
        <x-button>{{ $submitLabel }}</x-button>
    </div>
</form>
