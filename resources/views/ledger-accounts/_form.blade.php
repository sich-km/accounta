<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-label for="code" value="仕訳用勘定科目コード" />
        <x-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $ledgerAccount?->code)" required autofocus />
        <x-input-error for="code" class="mt-2" />
    </div>
    <div>
        <x-label for="name" value="勘定科目名" />
        <x-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $ledgerAccount?->name)" required />
        <x-input-error for="name" class="mt-2" />
    </div>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-label for="account_type" value="勘定科目区分" />
            <select id="account_type" name="account_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">選択してください</option>
                @foreach ($accountTypes as $accountType)
                    <option value="{{ $accountType->value }}" @selected(old('account_type', $ledgerAccount?->account_type?->value) === $accountType->value)>{{ $accountType->label() }}</option>
                @endforeach
            </select>
            <x-input-error for="account_type" class="mt-2" />
        </div>
        <div>
            <x-label for="normal_balance" value="通常残高" />
            <select id="normal_balance" name="normal_balance" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">選択してください</option>
                @foreach ($journalSides as $journalSide)
                    <option value="{{ $journalSide->value }}" @selected(old('normal_balance', $ledgerAccount?->normal_balance?->value) === $journalSide->value)>{{ $journalSide->label() }}</option>
                @endforeach
            </select>
            <x-input-error for="normal_balance" class="mt-2" />
        </div>
    </div>
    <p class="text-sm text-gray-500">通常残高は入力支援用です。仕訳時の借方・貸方は制限しません。</p>
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('ledger-accounts.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">キャンセル</a>
        <x-button>{{ $submitLabel }}</x-button>
    </div>
</form>
