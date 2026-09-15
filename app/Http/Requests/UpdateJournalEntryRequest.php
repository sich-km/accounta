<?php

namespace App\Http\Requests;

use App\Models\JournalEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\File;

class UpdateJournalEntryRequest extends JournalEntryRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $currentDocumentCount = $this->journalEntry()?->documents()->count() ?? 0;
        $remainingDocumentCount = max(0, 10 - $currentDocumentCount);

        return [
            ...parent::rules(),
            'documents' => ['nullable', 'array', "max:{$remainingDocumentCount}"],
            'documents.*' => ['file', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'documents.max' => '証憑は既存分を含めて1仕訳につき10件まで添付できます。',
        ];
    }

    protected function journalEntry(): ?JournalEntry
    {
        return JournalEntry::query()
            ->forOrganization($this->user()->organization_id)
            ->find($this->route('journal_entry'));
    }
}
