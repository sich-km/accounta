<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canManageMasters() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('accounts', 'code')->where(
                    fn ($query) => $query->where('organization_id', $this->user()->organization_id)
                ),
            ],
            'name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::in(array_keys(Account::TYPES))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => '勘定科目コードは英大文字・数字・アンダースコア・ハイフンで入力してください。',
            'code.unique' => 'この勘定科目コードは既に使用されています。',
            'account_type.in' => '勘定科目区分を選択してください。',
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
