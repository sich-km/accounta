@php
    $canSubmit = $departments->isNotEmpty() && $accounts->isNotEmpty();
@endphp

@unless ($canSubmit)
    <div class="mb-6 rounded-md bg-amber-50 p-4 text-sm text-amber-800">
        明細を登録するには、有効な部門と勘定科目をそれぞれ1件以上登録してください。
    </div>
@endunless

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-label for="period" value="対象年月" />
        <x-input
            id="period"
            name="period"
            type="month"
            min="1900-01"
            max="9999-12"
            class="mt-1 block w-full"
            :value="old('period', $amount?->period?->format('Y-m') ?? now()->format('Y-m'))"
            required
            autofocus
        />
        <x-input-error for="period" class="mt-2" />
    </div>

    <div>
        <x-label for="department_id" value="部門" />
        <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">選択してください</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((int) old('department_id', $amount?->department_id) === $department->id)>
                    {{ $department->code }} {{ $department->name }}{{ $department->is_active ? '' : '（無効）' }}
                </option>
            @endforeach
        </select>
        <x-input-error for="department_id" class="mt-2" />
    </div>

    <div>
        <x-label for="account_id" value="勘定科目" />
        <select id="account_id" name="account_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            <option value="">選択してください</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((int) old('account_id', $amount?->account_id) === $account->id)>
                    {{ $account->code }} {{ $account->name }}{{ $account->is_active ? '' : '（無効）' }}
                </option>
            @endforeach
        </select>
        <x-input-error for="account_id" class="mt-2" />
    </div>

    <fieldset>
        <legend class="block text-sm font-medium text-gray-700">区分</legend>
        <div class="mt-2 flex gap-6">
            @foreach ($amountTypes as $value => $label)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" name="type" value="{{ $value }}" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('type', $amount?->type ?? 'budget') === $value)>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <x-input-error for="type" class="mt-2" />
    </fieldset>

    <div>
        <x-label for="amount" value="金額" />
        <x-input
            id="amount"
            name="amount"
            type="text"
            inputmode="decimal"
            class="mt-1 block w-full"
            :value="old('amount', $amount?->amount)"
            required
        />
        <x-input-error for="amount" class="mt-2" />
    </div>

    <div>
        <x-label for="memo" value="メモ" />
        <textarea id="memo" name="memo" rows="4" maxlength="500" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('memo', $amount?->memo) }}</textarea>
        <x-input-error for="memo" class="mt-2" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('amounts.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">
            キャンセル
        </a>
        <x-button :disabled="! $canSubmit">{{ $submitLabel }}</x-button>
    </div>
</form>
