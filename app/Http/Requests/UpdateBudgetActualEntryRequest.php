<?php

namespace App\Http\Requests;

use App\Models\BudgetActualEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBudgetActualEntryRequest extends FormRequest
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
        $budgetActualEntry = BudgetActualEntry::query()
            ->forOrganization($this->user()->organization_id)
            ->find($this->route('budget_actual_entry'));

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
                                $budgetActualEntry,
                                fn ($activeQuery) => $activeQuery->orWhere('id', $budgetActualEntry->department_id)
                            ))
                ),
            ],
            'budget_actual_account_id' => [
                'required',
                'integer',
                Rule::exists('budget_actual_accounts', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $this->user()->organization_id)
                        ->where(fn ($masterQuery) => $masterQuery
                            ->where('is_active', true)
                            ->when(
                                $budgetActualEntry,
                                fn ($activeQuery) => $activeQuery->orWhere('id', $budgetActualEntry->budget_actual_account_id)
                            ))
                ),
            ],
            'type' => ['required', Rule::in(array_keys(BudgetActualEntry::TYPES))],
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
            'budget_actual_account_id.exists' => '有効な予実管理科目を選択してください。',
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
