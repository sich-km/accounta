<?php

namespace App\Http\Requests;

use App\Models\MonthlyAmount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateMonthlyAmountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $monthlyAmount = MonthlyAmount::query()
            ->forOrganization($this->user()->organization_id)
            ->find($this->route('amount'));

        return [
            'period' => [
                'required',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
                'date_format:Y-m',
                'after_or_equal:1900-01',
                'before_or_equal:9999-12',
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $this->user()->organization_id)
                        ->where(fn ($masterQuery) => $masterQuery
                            ->where('is_active', true)
                            ->when(
                                $monthlyAmount,
                                fn ($activeQuery) => $activeQuery->orWhere('id', $monthlyAmount->department_id)
                            ))
                ),
            ],
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $this->user()->organization_id)
                        ->where(fn ($masterQuery) => $masterQuery
                            ->where('is_active', true)
                            ->when(
                                $monthlyAmount,
                                fn ($activeQuery) => $activeQuery->orWhere('id', $monthlyAmount->account_id)
                            ))
                ),
            ],
            'type' => ['required', Rule::in(array_keys(MonthlyAmount::TYPES))],
            'amount' => ['required', 'regex:/^-?(?:0|[1-9]\d{0,12})(?:\.\d{1,2})?$/'],
            'memo' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.regex' => '対象年月はYYYY-MM形式で入力してください。',
            'department_id.exists' => '有効な部門を選択してください。',
            'account_id.exists' => '有効な勘定科目を選択してください。',
            'type.in' => '区分を選択してください。',
            'amount.regex' => '金額は整数13桁、小数2桁以内で入力してください。',
        ];
    }

    protected function prepareForValidation(): void
    {
        $memo = $this->input('memo');

        $this->merge([
            'period' => Str::of($this->input('period', ''))->trim()->toString(),
            'amount' => Str::of($this->input('amount', ''))->trim()->toString(),
            'memo' => $memo === null ? null : Str::of($memo)->trim()->toString(),
        ]);
    }
}
