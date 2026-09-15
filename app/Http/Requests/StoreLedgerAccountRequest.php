<?php

namespace App\Http\Requests;

use App\Enums\JournalSide;
use App\Enums\LedgerAccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreLedgerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageMasters() ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('ledger_accounts', 'code')->where(
                    fn ($query) => $query->where('organization_id', $this->user()->organization_id)
                ),
            ],
            'name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::enum(LedgerAccountType::class)],
            'normal_balance' => ['required', Rule::enum(JournalSide::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.regex' => '仕訳用勘定科目コードは英大文字または数字で始まり、英大文字・数字・_・-で入力してください。',
            'code.unique' => 'この仕訳用勘定科目コードは既に使用されています。',
            'account_type.enum' => '勘定科目区分を選択してください。',
            'normal_balance.enum' => '通常残高を選択してください。',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::of($this->input('code', ''))->trim()->upper()->toString(),
            'name' => Str::of($this->input('name', ''))->trim()->toString(),
        ]);
    }
}
