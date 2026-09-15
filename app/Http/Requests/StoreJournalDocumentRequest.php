<?php

namespace App\Http\Requests;

use App\Models\JournalEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreJournalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'document' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb')],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $journalEntry = JournalEntry::query()
                    ->forOrganization($this->user()->organization_id)
                    ->find($this->route('journal_entry'));

                if ($journalEntry?->documents()->count() >= 10) {
                    $validator->errors()->add('document', '1仕訳に添付できる証憑は10件までです。');
                }
            },
        ];
    }
}
