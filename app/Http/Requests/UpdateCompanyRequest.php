<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company instanceof Company
            && ($this->user()?->can('update', $company) ?? false);
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
                'regex:/^[a-z0-9][a-z0-9_-]*$/',
                Rule::unique('companies', 'code')->ignore($this->route('company')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'fiscal_year_start_month' => ['required', 'integer', 'between:1,12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => '企業コードは英小文字・数字・アンダースコア・ハイフンで入力してください。',
            'code.unique' => 'この企業コードは既に使用されています。',
            'fiscal_year_start_month.between' => '会計年度開始月は1月から12月の間で選択してください。',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::of($this->input('code', ''))->trim()->lower()->toString(),
            'name' => Str::of($this->input('name', ''))->trim()->toString(),
        ]);
    }
}
