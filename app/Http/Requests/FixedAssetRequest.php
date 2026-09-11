<?php

namespace App\Http\Requests;

use App\Models\FixedAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class FixedAssetRequest extends FormRequest
{
    private const MONEY_PATTERN = '/^(?:0|[1-9]\d{0,12})(?:\.\d{1,2})?$/';

    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $fixedAsset = $this->fixedAsset();
        $assetCodeRule = Rule::unique('fixed_assets', 'asset_code')
            ->where(fn ($query) => $query->where('organization_id', $this->user()->organization_id));

        if ($fixedAsset !== null) {
            $assetCodeRule->ignore($fixedAsset->id);
        }

        return [
            'asset_code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', $assetCodeRule],
            'asset_name' => ['required', 'string', 'max:100'],
            'asset_category' => ['required', Rule::in(array_keys(FixedAsset::ASSET_CATEGORIES))],
            'asset_category_detail' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('asset_category') === 'other'),
                'string',
                'max:100',
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $this->user()->organization_id)
                        ->where(fn ($departmentQuery) => $departmentQuery
                            ->where('is_active', true)
                            ->when(
                                $fixedAsset,
                                fn ($activeQuery) => $activeQuery->orWhere('id', $fixedAsset->department_id),
                            )),
                ),
            ],
            'acquisition_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:9999-12-31'],
            'service_start_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:acquisition_date', 'before_or_equal:9999-12-31'],
            'acquisition_cost' => ['required', 'regex:'.self::MONEY_PATTERN],
            'useful_life_years' => ['nullable', 'integer', 'between:1,255'],
            'depreciation_method' => ['required', Rule::in(array_keys(FixedAsset::DEPRECIATION_METHODS))],
            'residual_value' => ['required', 'regex:'.self::MONEY_PATTERN],
            'current_period_depreciation_expense' => ['required', 'regex:'.self::MONEY_PATTERN],
            'accumulated_depreciation' => ['required', 'regex:'.self::MONEY_PATTERN],
            'status' => ['required', Rule::in(array_keys(FixedAsset::STATUSES))],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $acquisitionCost = $this->amountInCents('acquisition_cost');
                $residualValue = $this->amountInCents('residual_value');
                $currentPeriodExpense = $this->amountInCents('current_period_depreciation_expense');
                $accumulatedDepreciation = $this->amountInCents('accumulated_depreciation');
                $depreciationMethod = $this->input('depreciation_method');

                if (
                    in_array($this->input('asset_category'), FixedAsset::NON_DEPRECIABLE_CATEGORIES, true)
                    && $depreciationMethod !== 'not_applicable'
                ) {
                    $validator->errors()->add('depreciation_method', '土地と建設仮勘定の償却方法は「償却対象外」を選択してください。');
                }

                if ($depreciationMethod === 'not_applicable' && $currentPeriodExpense !== null && $currentPeriodExpense !== 0) {
                    $validator->errors()->add('current_period_depreciation_expense', '償却対象外の資産は当期減価償却費を0にしてください。');
                }

                if ($depreciationMethod === 'not_applicable' && $accumulatedDepreciation !== null && $accumulatedDepreciation !== 0) {
                    $validator->errors()->add('accumulated_depreciation', '償却対象外の資産は減価償却累計額を0にしてください。');
                }

                if ($acquisitionCost !== null && $acquisitionCost === 0) {
                    $validator->errors()->add('acquisition_cost', '取得価額は0より大きい金額を入力してください。');
                }

                if ($acquisitionCost !== null && $residualValue !== null && $residualValue > $acquisitionCost) {
                    $validator->errors()->add('residual_value', '残存価額は取得価額以下で入力してください。');
                }

                if (
                    $acquisitionCost !== null
                    && $residualValue !== null
                    && $accumulatedDepreciation !== null
                    && $accumulatedDepreciation > $acquisitionCost - $residualValue
                ) {
                    $validator->errors()->add('accumulated_depreciation', '減価償却累計額は取得価額から残存価額を差し引いた金額以下で入力してください。');
                }

                if (
                    $currentPeriodExpense !== null
                    && $accumulatedDepreciation !== null
                    && $currentPeriodExpense > $accumulatedDepreciation
                ) {
                    $validator->errors()->add('current_period_depreciation_expense', '当期減価償却費は減価償却累計額以下で入力してください。');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'asset_code.regex' => '資産コードは英大文字または数字で始まり、英大文字・数字・_・-で入力してください。',
            'asset_code.unique' => 'この資産コードは既に使用されています。',
            'asset_category.in' => '資産区分を選択してください。',
            'asset_category_detail.required' => '資産区分が「その他」の場合は、その他区分名を入力してください。',
            'department_id.exists' => '有効な部門を選択してください。',
            'acquisition_date.date_format' => '取得日はYYYY-MM-DD形式で入力してください。',
            'service_start_date.date_format' => '供用開始日はYYYY-MM-DD形式で入力してください。',
            'service_start_date.after_or_equal' => '供用開始日は取得日以後の日付を入力してください。',
            'acquisition_cost.regex' => '取得価額は整数13桁、小数2桁以内の正の金額で入力してください。',
            'residual_value.regex' => '残存価額は整数13桁、小数2桁以内の0以上の金額で入力してください。',
            'current_period_depreciation_expense.regex' => '当期減価償却費は整数13桁、小数2桁以内の0以上の金額で入力してください。',
            'accumulated_depreciation.regex' => '減価償却累計額は整数13桁、小数2桁以内の0以上の金額で入力してください。',
            'depreciation_method.in' => '償却方法を選択してください。',
            'status.in' => '資産状態を選択してください。',
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableValue = fn (string $key): ?string => filled($this->input($key))
            ? Str::of($this->input($key))->trim()->toString()
            : null;
        $assetCategory = Str::of($this->input('asset_category', ''))->trim()->toString();

        $this->merge([
            'asset_code' => Str::of($this->input('asset_code', ''))->trim()->upper()->toString(),
            'asset_name' => Str::of($this->input('asset_name', ''))->trim()->toString(),
            'asset_category' => $assetCategory,
            'asset_category_detail' => $assetCategory === 'other' ? $nullableValue('asset_category_detail') : null,
            'acquisition_date' => Str::of($this->input('acquisition_date', ''))->trim()->toString(),
            'service_start_date' => $nullableValue('service_start_date'),
            'acquisition_cost' => Str::of($this->input('acquisition_cost', ''))->trim()->toString(),
            'useful_life_years' => $nullableValue('useful_life_years'),
            'residual_value' => Str::of($this->input('residual_value', ''))->trim()->toString(),
            'current_period_depreciation_expense' => Str::of($this->input('current_period_depreciation_expense', ''))->trim()->toString(),
            'accumulated_depreciation' => Str::of($this->input('accumulated_depreciation', ''))->trim()->toString(),
            'notes' => $nullableValue('notes'),
        ]);
    }

    abstract protected function fixedAsset(): ?FixedAsset;

    private function amountInCents(string $key): ?int
    {
        $amount = $this->input($key);

        if (! is_string($amount) || preg_match(self::MONEY_PATTERN, $amount) !== 1) {
            return null;
        }

        [$integerPart, $decimalPart] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $integerPart * 100) + (int) str_pad($decimalPart, 2, '0');
    }
}
