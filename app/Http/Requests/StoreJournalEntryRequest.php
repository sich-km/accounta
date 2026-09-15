<?php

namespace App\Http\Requests;

use App\Models\JournalEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\File;

class StoreJournalEntryRequest extends JournalEntryRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'documents.max' => '証憑は10件まで添付できます。',
        ];
    }

    protected function journalEntry(): ?JournalEntry
    {
        return null;
    }
}
