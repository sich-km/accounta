<?php

namespace App\Http\Requests;

use App\Enums\JournalSide;
use App\Models\JournalEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class JournalEntryRequest extends FormRequest
{
    private const MONEY_PATTERN = '/^(?:0|[1-9]\d{0,12})(?:\.\d{1,2})?$/';

    public function authorize(): bool
    {
        return $this->user()?->organization_id !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $journalEntry = $this->journalEntry();
        $organizationId = $this->user()->organization_id;
        $currentLedgerAccountIds = $journalEntry?->lines()->pluck('ledger_account_id')->all() ?? [];
        $currentDepartmentIds = $journalEntry?->lines()->whereNotNull('department_id')->pluck('department_id')->all() ?? [];
        $currentOriginatingDepartmentId = $journalEntry?->originating_department_id;

        return [
            'entry_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:9999-12-31'],
            'originating_department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $organizationId)
                        ->where(fn ($departmentQuery) => $departmentQuery
                            ->where('is_active', true)
                            ->when($currentOriginatingDepartmentId !== null, fn ($activeQuery) => $activeQuery->orWhere('id', $currentOriginatingDepartmentId))),
                ),
            ],
            'description' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'between:2,100'],
            'lines.*.side' => ['required', Rule::enum(JournalSide::class)],
            'lines.*.ledger_account_id' => [
                'required',
                'integer',
                Rule::exists('ledger_accounts', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $organizationId)
                        ->where(fn ($accountQuery) => $accountQuery
                            ->where('is_active', true)
                            ->when($currentLedgerAccountIds !== [], fn ($activeQuery) => $activeQuery->orWhereIn('id', $currentLedgerAccountIds))),
                ),
            ],
            'lines.*.department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $organizationId)
                        ->where(fn ($departmentQuery) => $departmentQuery
                            ->where('is_active', true)
                            ->when($currentDepartmentIds !== [], fn ($activeQuery) => $activeQuery->orWhereIn('id', $currentDepartmentIds))),
                ),
            ],
            'lines.*.amount' => ['required', 'regex:'.self::MONEY_PATTERN],
            'lines.*.description' => ['nullable', 'string'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $lines = $this->input('lines');

                if (! is_array($lines)) {
                    return;
                }

                $debitTotal = 0;
                $creditTotal = 0;
                $hasDebit = false;
                $hasCredit = false;

                foreach ($lines as $line) {
                    if (! is_array($line)) {
                        continue;
                    }

                    $amount = $this->amountToCents($line['amount'] ?? null);
                    $side = $line['side'] ?? null;

                    if ($amount === 0) {
                        $validator->errors()->add('lines', '金額は0より大きい値を入力してください。');
                    }

                    if ($amount === null) {
                        continue;
                    }

                    if ($side === JournalSide::Debit->value) {
                        $hasDebit = true;
                        $debitTotal += $amount;
                    }

                    if ($side === JournalSide::Credit->value) {
                        $hasCredit = true;
                        $creditTotal += $amount;
                    }
                }

                if (! $hasDebit || ! $hasCredit) {
                    $validator->errors()->add('lines', '借方明細と貸方明細を1件以上ずつ入力してください。');
                }

                if ($hasDebit && $hasCredit && $debitTotal !== $creditTotal) {
                    $validator->errors()->add('lines', '借方合計と貸方合計を一致させてください。');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'entry_date.date_format' => '仕訳日はYYYY-MM-DD形式で入力してください。',
            'originating_department_id.exists' => '有効な起票部門を選択してください。',
            'lines.between' => '明細は2件以上100件以下で入力してください。',
            'lines.*.side.enum' => '貸借区分を選択してください。',
            'lines.*.ledger_account_id.exists' => '有効な仕訳用勘定科目を選択してください。',
            'lines.*.department_id.exists' => '有効な部門を選択してください。',
            'lines.*.amount.regex' => '金額は整数13桁、小数2桁以内の正の値で入力してください。',
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableValue = fn (mixed $value): ?string => filled($value)
            ? Str::of($value)->trim()->toString()
            : null;
        $lines = $this->input('lines');

        if (is_array($lines)) {
            $lines = array_values(array_map(
                fn (mixed $line): mixed => is_array($line) ? [
                    'side' => $line['side'] ?? null,
                    'ledger_account_id' => $line['ledger_account_id'] ?? null,
                    'department_id' => filled($line['department_id'] ?? null) ? $line['department_id'] : null,
                    'amount' => Str::of($line['amount'] ?? '')->trim()->toString(),
                    'description' => $nullableValue($line['description'] ?? null),
                ] : $line,
                $lines,
            ));
        }

        $this->merge([
            'entry_date' => Str::of($this->input('entry_date', ''))->trim()->toString(),
            'originating_department_id' => filled($this->input('originating_department_id'))
                ? $this->input('originating_department_id')
                : null,
            'description' => Str::of($this->input('description', ''))->trim()->toString(),
            'notes' => $nullableValue($this->input('notes')),
            'lines' => $lines,
        ]);
    }

    abstract protected function journalEntry(): ?JournalEntry;

    private function amountToCents(mixed $amount): ?int
    {
        if (! is_string($amount) || preg_match(self::MONEY_PATTERN, $amount) !== 1) {
            return null;
        }

        [$integerPart, $decimalPart] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $integerPart * 100) + (int) str_pad($decimalPart, 2, '0');
    }
}
