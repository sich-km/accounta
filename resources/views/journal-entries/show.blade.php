<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">仕訳詳細 #{{ $journalEntry->id }}</h2>
                <p class="mt-1 text-base text-gray-700">{{ $journalEntry->entry_date->format('Y/m/d') }} / {{ $journalEntry->description }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-back-link href="{{ route('journal-entries.index') }}" class="text-sm">一覧へ戻る</x-back-link>
                <x-button href="{{ route('journal-entries.edit', $journalEntry) }}" class="text-sm">編集</x-button>
                <x-danger-button type="button" class="text-sm" @click="$dispatch('open-modal', 'confirm-journal-entry-deletion')">
                    削除
                </x-danger-button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-base font-medium text-green-900">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-md bg-amber-50 p-4 text-base font-medium text-amber-900">{{ session('warning') }}</div>
            @endif

            <section class="overflow-hidden border border-gray-200 bg-white shadow-sm sm:rounded-lg">
                <dl class="grid grid-cols-1 sm:grid-cols-2">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <dt class="text-sm font-semibold text-gray-700">仕訳日</dt>
                        <dd class="mt-1 text-base font-medium text-gray-900">{{ $journalEntry->entry_date->format('Y/m/d') }}</dd>
                    </div>
                    <div class="border-b border-gray-200 px-6 py-4 sm:border-l">
                        <dt class="text-sm font-semibold text-gray-700">摘要</dt>
                        <dd class="mt-1 text-base font-medium text-gray-900">{{ $journalEntry->description }}</dd>
                    </div>
                    <div class="border-b border-gray-200 px-6 py-4 sm:col-span-2">
                        <dt class="text-sm font-semibold text-gray-700">起票部門</dt>
                        <dd class="mt-1 text-base font-medium text-gray-900">{{ $journalEntry->originatingDepartment ? $journalEntry->originatingDepartment->code.' '.$journalEntry->originatingDepartment->name : '未設定' }}</dd>
                    </div>
                    <div class="border-b border-gray-200 px-6 py-4 sm:col-span-2">
                        <dt class="text-sm font-semibold text-gray-700">備考</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-base text-gray-900">{{ $journalEntry->notes ?? '未設定' }}</dd>
                    </div>
                    <div class="border-b border-gray-200 px-6 py-4 sm:border-b-0">
                        <dt class="text-sm font-semibold text-gray-700">登録日時</dt>
                        <dd class="mt-1 text-base text-gray-900">{{ $journalEntry->created_at->format('Y/m/d H:i:s') }}</dd>
                    </div>
                    <div class="px-6 py-4 sm:border-l">
                        <dt class="text-sm font-semibold text-gray-700">更新日時</dt>
                        <dd class="mt-1 text-base text-gray-900">{{ $journalEntry->updated_at->format('Y/m/d H:i:s') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-xl font-semibold text-gray-900">仕訳明細</h3>
                    <p class="text-base font-medium text-gray-700">{{ $journalEntry->lines->count() }}行</p>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 lg:items-start">
                    @foreach ([
                        ['value' => \App\Enums\JournalSide::Debit, 'label' => '借方', 'panel' => 'border-sky-200 bg-sky-50/40', 'heading' => 'text-sky-900'],
                        ['value' => \App\Enums\JournalSide::Credit, 'label' => '貸方', 'panel' => 'border-indigo-200 bg-indigo-50/40', 'heading' => 'text-indigo-900'],
                    ] as $side)
                        <section class="overflow-hidden rounded-xl border {{ $side['panel'] }}" aria-labelledby="{{ $side['value']->value }}-heading">
                            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                                <h4 id="{{ $side['value']->value }}-heading" class="text-base font-semibold {{ $side['heading'] }}">{{ $side['label'] }}明細</h4>
                                <p class="text-base font-semibold tabular-nums text-gray-900">
                                    合計 {{ number_format((float) ($side['value'] === \App\Enums\JournalSide::Debit ? $journalEntry->debitTotal() : $journalEntry->creditTotal()), 2) }}
                                </p>
                            </div>

                            <div class="space-y-3 p-3 sm:p-4">
                                @forelse ($journalEntry->lines->where('side', $side['value']) as $line)
                                    <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="flex items-start justify-between gap-4">
                                            <p class="text-sm font-semibold text-gray-700">No. {{ $line->line_number }}</p>
                                            <p class="whitespace-nowrap text-lg font-semibold tabular-nums text-gray-900">{{ number_format((float) $line->amount, 2) }}</p>
                                        </div>

                                        <dl class="mt-3 space-y-3">
                                            <div>
                                                <dt class="text-sm font-semibold text-gray-700">勘定科目</dt>
                                                <dd class="mt-1 text-base font-medium text-gray-900">{{ $line->ledgerAccount->code }} {{ $line->ledgerAccount->name }}</dd>
                                            </div>
                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <div>
                                                    <dt class="text-sm font-semibold text-gray-700">個別部門</dt>
                                                    <dd class="mt-1 text-base text-gray-900">{{ $line->department ? $line->department->code.' '.$line->department->name : '未設定' }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-sm font-semibold text-gray-700">明細摘要</dt>
                                                    <dd class="mt-1 text-base text-gray-900">{{ $line->description ?? '—' }}</dd>
                                                </div>
                                            </div>
                                        </dl>
                                    </article>
                                @empty
                                    <p class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-base text-gray-700">{{ $side['label'] }}明細はありません。</p>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 gap-3 rounded-lg bg-gray-900 p-4 text-white sm:grid-cols-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-300">借方合計</p>
                        <p class="mt-1 text-xl font-semibold tabular-nums">{{ number_format((float) $journalEntry->debitTotal(), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-300">貸方合計</p>
                        <p class="mt-1 text-xl font-semibold tabular-nums">{{ number_format((float) $journalEntry->creditTotal(), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-300">貸借</p>
                        <p class="mt-1 text-xl font-semibold text-emerald-300">一致</p>
                    </div>
                </div>
            </section>

            <section id="documents" class="scroll-mt-4 space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">証憑</h3>
                    <p class="mt-1 text-base text-gray-700">PDF・JPEG・PNGを10MiBまで、1仕訳につき10件まで添付できます。</p>
                </div>
                <form method="POST" action="{{ route('journal-entries.documents.store', $journalEntry) }}" enctype="multipart/form-data" class="flex flex-col gap-3 rounded-lg border border-dashed border-gray-300 bg-white p-4 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <x-label for="document" value="証憑ファイル" />
                        <input id="document" name="document" type="file" accept="application/pdf,image/jpeg,image/png" class="mt-1 block w-full text-base text-gray-900" required>
                        <x-input-error for="document" class="mt-2" />
                    </div>
                    <x-button>添付する</x-button>
                </form>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">ファイル名</th>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">MIME Type</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">サイズ</th>
                                <th class="whitespace-nowrap px-4 py-3 text-left text-sm font-semibold text-gray-800">登録日時</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">操作</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($journalEntry->documents as $document)
                                    <tr>
                                        <td class="px-4 py-4 text-base font-medium text-gray-900">{{ $document->original_name }}</td>
                                        <td class="px-4 py-4 text-base text-gray-900">{{ $document->mime_type }}</td>
                                        <td class="px-4 py-4 text-right text-base text-gray-900">{{ number_format($document->file_size / 1024, 1) }} KB</td>
                                        <td class="px-4 py-4 text-base text-gray-900">{{ $document->created_at->format('Y/m/d H:i:s') }}</td>
                                        <td class="px-4 py-4 text-right text-base"><div class="flex justify-end gap-3">
                                            <a href="{{ route('journal-entries.documents.show', [$journalEntry, $document]) }}" target="_blank" rel="noopener" class="font-medium text-sky-700 hover:text-sky-900">確認</a>
                                            <a href="{{ route('journal-entries.documents.show', [$journalEntry, $document, 'download' => 1]) }}" class="font-medium text-indigo-700 hover:text-indigo-900">ダウンロード</a>
                                            <form method="POST" action="{{ route('journal-entries.documents.destroy', [$journalEntry, $document]) }}" onsubmit="return confirm('この証憑の紐付けを解除し、ファイルを削除しますか？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-700 hover:text-red-900">解除</button>
                                            </form>
                                        </div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-10 text-center text-base text-gray-700">証憑は添付されていません。</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

        <x-confirmation-modal id="confirm-journal-entry-deletion" max-width="md" :standalone="true">
            <x-slot name="title">仕訳の削除</x-slot>

            <x-slot name="content">
                <p>
                    <span class="font-semibold text-gray-900">#{{ $journalEntry->id }} {{ $journalEntry->description }}</span>
                    と添付済みの証憑を削除します。この操作は取り消せません。
                </p>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button type="button" @click="$dispatch('close')">
                    キャンセル
                </x-secondary-button>

                <form method="POST" action="{{ route('journal-entries.destroy', $journalEntry) }}" class="ms-3">
                    @csrf
                    @method('DELETE')
                    <x-danger-button type="submit">
                        削除する
                    </x-danger-button>
                </form>
            </x-slot>
        </x-confirmation-modal>
    </div>
</x-app-layout>
