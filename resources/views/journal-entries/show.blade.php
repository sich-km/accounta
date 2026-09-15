<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">仕訳詳細 #{{ $journalEntry->id }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $journalEntry->entry_date->format('Y/m/d') }} / {{ $journalEntry->description }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('journal-entries.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">一覧へ戻る</a>
                <a href="{{ route('journal-entries.edit', $journalEntry) }}" class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">編集</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">{{ session('warning') }}</div>
            @endif

            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <dl class="grid grid-cols-1 sm:grid-cols-2">
                    <div class="border-b border-gray-200 px-6 py-4"><dt class="text-sm font-medium text-gray-500">仕訳日</dt><dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->entry_date->format('Y/m/d') }}</dd></div>
                    <div class="border-b border-gray-200 px-6 py-4 sm:border-l"><dt class="text-sm font-medium text-gray-500">摘要</dt><dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->description }}</dd></div>
                    <div class="border-b border-gray-200 px-6 py-4 sm:col-span-2"><dt class="text-sm font-medium text-gray-500">起票部門</dt><dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->originatingDepartment ? $journalEntry->originatingDepartment->code.' '.$journalEntry->originatingDepartment->name : '未設定' }}</dd></div>
                    <div class="border-b border-gray-200 px-6 py-4 sm:col-span-2"><dt class="text-sm font-medium text-gray-500">備考</dt><dd class="mt-1 whitespace-pre-wrap text-sm text-gray-900">{{ $journalEntry->notes ?? '未設定' }}</dd></div>
                    <div class="border-b border-gray-200 px-6 py-4"><dt class="text-sm font-medium text-gray-500">登録日時</dt><dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->created_at->format('Y/m/d H:i:s') }}</dd></div>
                    <div class="border-b border-gray-200 px-6 py-4 sm:border-l"><dt class="text-sm font-medium text-gray-500">更新日時</dt><dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->updated_at->format('Y/m/d H:i:s') }}</dd></div>
                </dl>
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between gap-4"><h3 class="text-lg font-semibold text-gray-900">仕訳明細</h3><p class="text-sm text-gray-500">{{ $journalEntry->lines->count() }}行</p></div>
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">貸借</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">勘定科目</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">部門</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">金額</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">明細摘要</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($journalEntry->lines as $line)
                                    <tr class="{{ $line->side === \App\Enums\JournalSide::Debit ? 'bg-sky-50/40' : 'bg-indigo-50/40' }}">
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $line->line_number }}</td>
                                        <td class="px-4 py-4 text-sm font-semibold {{ $line->side === \App\Enums\JournalSide::Debit ? 'text-sky-700' : 'text-indigo-700' }}">{{ $line->side->label() }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900">{{ $line->ledgerAccount->code }} {{ $line->ledgerAccount->name }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $line->department ? $line->department->code.' '.$line->department->name : '未設定' }}</td>
                                        <td class="px-4 py-4 text-right text-sm font-medium text-gray-900">{{ number_format((float) $line->amount, 2) }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $line->description ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-900 text-white"><tr>
                                <th colspan="2" class="px-4 py-3 text-left text-sm">貸借合計</th>
                                <td class="px-4 py-3 text-sm">借方 {{ number_format((float) $journalEntry->debitTotal(), 2) }}</td>
                                <td class="px-4 py-3 text-sm">貸方 {{ number_format((float) $journalEntry->creditTotal(), 2) }}</td>
                                <td colspan="2" class="px-4 py-3 text-right text-sm font-semibold text-emerald-300">一致</td>
                            </tr></tfoot>
                        </table>
                    </div>
                </div>
            </section>

            <section id="documents" class="scroll-mt-4 space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">証憑</h3>
                    <p class="mt-1 text-sm text-gray-500">PDF・JPEG・PNGを10MiBまで、1仕訳につき10件まで添付できます。</p>
                </div>
                <form method="POST" action="{{ route('journal-entries.documents.store', $journalEntry) }}" enctype="multipart/form-data" class="flex flex-col gap-3 rounded-lg border border-dashed border-gray-300 bg-white p-4 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <x-label for="document" value="証憑ファイル" />
                        <input id="document" name="document" type="file" accept="application/pdf,image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-700" required>
                        <x-input-error for="document" class="mt-2" />
                    </div>
                    <x-button>添付する</x-button>
                </form>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">ファイル名</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">MIME Type</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">サイズ</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">登録日時</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">操作</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($journalEntry->documents as $document)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ $document->original_name }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $document->mime_type }}</td>
                                        <td class="px-4 py-4 text-right text-sm text-gray-700">{{ number_format($document->file_size / 1024, 1) }} KB</td>
                                        <td class="px-4 py-4 text-sm text-gray-700">{{ $document->created_at->format('Y/m/d H:i:s') }}</td>
                                        <td class="px-4 py-4 text-right text-sm"><div class="flex justify-end gap-3">
                                            <a href="{{ route('journal-entries.documents.show', [$journalEntry, $document]) }}" target="_blank" rel="noopener" class="text-sky-600 hover:text-sky-900">確認</a>
                                            <a href="{{ route('journal-entries.documents.show', [$journalEntry, $document, 'download' => 1]) }}" class="text-indigo-600 hover:text-indigo-900">ダウンロード</a>
                                            <form method="POST" action="{{ route('journal-entries.documents.destroy', [$journalEntry, $document]) }}" onsubmit="return confirm('この証憑の紐付けを解除し、ファイルを削除しますか？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">解除</button>
                                            </form>
                                        </div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">証憑は添付されていません。</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <form method="POST" action="{{ route('journal-entries.destroy', $journalEntry) }}" onsubmit="return confirm('この仕訳と添付済み証憑を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button type="submit">仕訳を削除</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
