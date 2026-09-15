@php
    $defaultLines = [];
    $savedLines = $journalEntry?->lines->map(fn ($line) => [
        'side' => $line->side->value,
        'ledger_account_id' => (string) $line->ledger_account_id,
        'department_id' => $line->department_id === null ? '' : (string) $line->department_id,
        'amount' => $line->amount,
        'description' => $line->description ?? '',
    ])->values()->all();
    $initialLines = old('lines', $savedLines ?: $defaultLines);
    $ledgerAccountNames = $ledgerAccounts->mapWithKeys(fn ($ledgerAccount) => [
        (string) $ledgerAccount->id => $ledgerAccount->name,
    ])->all();
    $initialOriginatingDepartmentId = old(
        'originating_department_id',
        $journalEntry?->originating_department_id ?? $userDepartmentId ?? '',
    );

    $initialEntryDate = old('entry_date', $journalEntry?->entry_date?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $currentDocumentCount = $journalEntry?->documents_count ?? 0;
    $remainingDocumentCount = max(0, 10 - $currentDocumentCount);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="space-y-8"
    x-data="{
        accountNames: {{ Js::from($ledgerAccountNames) }},
        entryDate: {{ Js::from($initialEntryDate) }},
        originatingDepartmentId: {{ Js::from((string) $initialOriginatingDepartmentId) }},
        lines: {{ Js::from($initialLines) }}.map((line, index) => ({
            ...line,
            department_id: String(line.department_id ?? ''),
            description: String(line.description ?? ''),
            _key: index,
            _detailsOpen: false,
            _descriptionOverridden: String(line.description ?? '') !== '',
        })),
        nextLineKey: {{ count($initialLines) }},
        init() {
            this.lines.forEach(line => {
                line._descriptionOverridden = line.description !== ''
                    && line.description !== this.suggestedLineDescription(line);
            });
        },
        newLine(side) {
            return {
                _key: this.nextLineKey++,
                _detailsOpen: false,
                _descriptionOverridden: false,
                side,
                ledger_account_id: '',
                department_id: this.originatingDepartmentId,
                amount: '',
                description: '',
            };
        },
        startEntry() {
            if (this.lines.length === 0) {
                this.lines.push(this.newLine('debit'), this.newLine('credit'));
            }
        },
        addLine(side) { this.lines.push(this.newLine(side)) },
        lineItems(side) { return this.lines.map((line, index) => ({ line, index })).filter(item => item.line.side === side) },
        suggestedLineDescription(line) {
            const accountName = this.accountNames[String(line.ledger_account_id)];
            const dateParts = this.entryDate.match(/^(\d{4})-(\d{2})-\d{2}$/);

            return accountName && dateParts
                ? `${dateParts[1]}年${Number(dateParts[2])}月の${accountName}`
                : '';
        },
        applyAccountDefaults(line) {
            if (! line._descriptionOverridden) {
                line.description = this.suggestedLineDescription(line);
            }
        },
        applyDateDefaults() {
            this.lines.filter(line => ! line._descriptionOverridden).forEach(line => line.description = this.suggestedLineDescription(line));
        },
        canRemove(side) { return this.lines.filter(line => line.side === side).length > 1 },
        removeLine(index, side) { if (this.canRemove(side)) this.lines.splice(index, 1) },
        total(side) { return this.lines.filter(line => line.side === side).reduce((sum, line) => sum + (Number(line.amount) || 0), 0) },
        money(value) { return value.toLocaleString('ja-JP', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    }"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="space-y-4">
        <div class="border-b border-gray-200 pb-2">
            <h3 class="text-lg font-semibold text-gray-900">証憑</h3>
            @if ($journalEntry === null)
                <p class="mt-1 text-sm text-gray-500">証憑を先に選択し、続けて仕訳情報と明細を入力できます。</p>
            @else
                <p class="mt-1 text-sm text-gray-500">更新と同時に新しい証憑を追加できます。現在 {{ $currentDocumentCount }} 件添付済みです。</p>
            @endif
        </div>
        <div>
            <x-label for="documents" value="証憑ファイル" />
            <input id="documents" name="documents[]" type="file" accept="application/pdf,image/jpeg,image/png" multiple @disabled($remainingDocumentCount === 0) class="mt-1 block w-full text-sm text-gray-700 disabled:cursor-not-allowed disabled:text-gray-400">
            <p class="mt-1 text-xs text-gray-500">
                PDF・JPEG・PNGを1ファイル10MiBまで、1仕訳につき合計10件添付できます。
                @if ($journalEntry !== null)
                    あと{{ $remainingDocumentCount }}件追加できます。既存証憑の確認・解除は<a href="{{ route('journal-entries.show', $journalEntry) }}#documents" class="underline hover:text-gray-700">詳細画面</a>で行えます。
                @endif
            </p>
            <x-input-error for="documents" class="mt-2" />
            @foreach ($errors->get('documents.*') as $messages)
                @foreach ($messages as $message)
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @endforeach
            @endforeach
        </div>
    </section>

    <section class="space-y-5">
        <h3 class="border-b border-gray-200 pb-2 text-lg font-semibold text-gray-900">仕訳情報</h3>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
            <div>
                <x-label for="entry_date" value="仕訳日" />
                <x-input id="entry_date" name="entry_date" type="date" class="mt-1 block w-full" x-model="entryDate" @change="entryDate = $event.target.value; applyDateDefaults()" required />
                <x-input-error for="entry_date" class="mt-2" />
            </div>
            <div>
                <x-label for="originating_department_id" value="起票部門" />
                <select id="originating_department_id" name="originating_department_id" x-model="originatingDepartmentId" @change="originatingDepartmentId = $event.target.value" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">未設定</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) $initialOriginatingDepartmentId === (string) $department->id)>{{ $department->code }} {{ $department->name }}{{ $department->is_active ? '' : '（無効）' }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">この仕訳を起票する部門です。新しく追加する明細の個別部門にも使用します。</p>
                <x-input-error for="originating_department_id" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-label for="description" value="摘要" />
                <x-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $journalEntry?->description)" required />
                <x-input-error for="description" class="mt-2" />
            </div>
        </div>
        <div>
            <x-label for="notes" value="備考" />
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $journalEntry?->notes) }}</textarea>
            <x-input-error for="notes" class="mt-2" />
        </div>
    </section>

    <section class="space-y-4">
        <div class="border-b border-gray-200 pb-2">
            <h3 class="text-lg font-semibold text-gray-900">仕訳明細</h3>
            <p class="mt-1 text-sm text-gray-500">借方と貸方を分けて入力してください。各側1行以上必要です。</p>
        </div>

        <x-input-error for="lines" />
        @foreach ($errors->get('lines.*') as $messages)
            @foreach ($messages as $message)
                <p class="text-sm text-red-600">{{ $message }}</p>
            @endforeach
        @endforeach

        <div x-cloak x-show="lines.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center">
            <p class="text-sm text-gray-600">借方・貸方の明細を入力します。</p>
            <x-button type="button" class="mt-4" @click="startEntry()">仕訳入力</x-button>
        </div>

        <div x-cloak x-show="lines.length > 0" class="grid grid-cols-1 gap-5 lg:grid-cols-2 lg:items-start">
            @foreach ([
                ['value' => 'debit', 'label' => '借方', 'panel' => 'border-sky-200 bg-sky-50/40', 'heading' => 'text-sky-900', 'button' => 'border-sky-300 bg-white text-sky-700 hover:bg-sky-100'],
                ['value' => 'credit', 'label' => '貸方', 'panel' => 'border-indigo-200 bg-indigo-50/40', 'heading' => 'text-indigo-900', 'button' => 'border-indigo-300 bg-white text-indigo-700 hover:bg-indigo-100'],
            ] as $side)
                <section class="overflow-hidden rounded-xl border {{ $side['panel'] }}" aria-labelledby="{{ $side['value'] }}-heading">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                        <div>
                            <h4 id="{{ $side['value'] }}-heading" class="font-semibold {{ $side['heading'] }}">{{ $side['label'] }}明細</h4>
                            <p class="mt-0.5 text-xs text-gray-600">
                                合計 <span class="font-semibold tabular-nums" x-text="money(total('{{ $side['value'] }}'))"></span>
                            </p>
                        </div>
                        <button type="button" @click="addLine('{{ $side['value'] }}')" class="shrink-0 rounded-md border px-3 py-2 text-xs font-semibold {{ $side['button'] }}">{{ $side['label'] }}明細を追加</button>
                    </div>

                    <div class="space-y-3 p-3 sm:p-4">
                        <template x-for="item in lineItems('{{ $side['value'] }}')" :key="item.line._key">
                            <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                                <input type="hidden" :name="`lines[${item.index}][side]`" value="{{ $side['value'] }}">

                                <div class="flex items-start gap-2">
                                    <div class="min-w-0 flex-1 space-y-3">
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-5">
                                            <div class="sm:col-span-3">
                                                <label class="block text-xs font-medium text-gray-700">勘定科目</label>
                                                <select x-model="item.line.ledger_account_id" @change="item.line.ledger_account_id = $event.target.value; applyAccountDefaults(item.line)" :name="`lines[${item.index}][ledger_account_id]`" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                                    <option value="">選択してください</option>
                                                    @foreach ($ledgerAccounts as $ledgerAccount)
                                                        <option value="{{ $ledgerAccount->id }}">{{ $ledgerAccount->code }} {{ $ledgerAccount->name }}{{ $ledgerAccount->is_active ? '' : '（無効）' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-xs font-medium text-gray-700">金額</label>
                                                <input x-model="item.line.amount" :name="`lines[${item.index}][amount]`" type="number" min="0.01" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-right text-sm tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            </div>
                                        </div>

                                        <div x-cloak x-show="item.line._detailsOpen" class="grid grid-cols-1 gap-3 rounded-md bg-gray-50 p-3 sm:grid-cols-2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">個別部門</label>
                                                <select x-model="item.line.department_id" :name="`lines[${item.index}][department_id]`" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">未設定</option>
                                                    @foreach ($departments as $department)
                                                        <option value="{{ $department->id }}">{{ $department->code }} {{ $department->name }}{{ $department->is_active ? '' : '（無効）' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">明細摘要</label>
                                                <input x-model="item.line.description" @input="item.line._descriptionOverridden = true" :name="`lines[${item.index}][description]`" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1 pt-5">
                                        <button type="button" @click="item.line._detailsOpen = !item.line._detailsOpen" :aria-expanded="item.line._detailsOpen" title="詳細設定" class="inline-flex size-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                                            <svg class="size-4 transition-transform" :class="item.line._detailsOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                            </svg>
                                            <span class="sr-only">明細の詳細設定を開閉</span>
                                        </button>
                                        <button type="button" @click="removeLine(item.index, '{{ $side['value'] }}')" :disabled="!canRemove('{{ $side['value'] }}')" title="明細を削除" class="inline-flex size-8 items-center justify-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 disabled:cursor-not-allowed disabled:text-gray-300 disabled:hover:bg-transparent">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3m-9 0 1 13h10l1-13M10 11v5m4-5v5" />
                                            </svg>
                                            <span class="sr-only">この明細を削除</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            @endforeach
        </div>

        <div x-cloak x-show="lines.length > 0" class="grid grid-cols-1 gap-3 rounded-lg bg-gray-900 p-4 text-white sm:grid-cols-3">
            <div><p class="text-xs text-gray-300">借方合計</p><p class="mt-1 text-xl font-semibold" x-text="money(total('debit'))"></p></div>
            <div><p class="text-xs text-gray-300">貸方合計</p><p class="mt-1 text-xl font-semibold" x-text="money(total('credit'))"></p></div>
            <div><p class="text-xs text-gray-300">差額</p><p class="mt-1 text-xl font-semibold" :class="Math.abs(total('debit') - total('credit')) < 0.001 ? 'text-emerald-300' : 'text-amber-300'" x-text="money(total('debit') - total('credit'))"></p></div>
        </div>
    </section>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ $cancelUrl }}" class="text-sm text-gray-600 underline hover:text-gray-900">キャンセル</a>
        <x-button>{{ $submitLabel }}</x-button>
    </div>
</form>
