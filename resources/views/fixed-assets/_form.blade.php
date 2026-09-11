@php
    $canSubmit = $departments->isNotEmpty();
@endphp

@unless ($canSubmit)
    <div class="mb-6 rounded-md bg-amber-50 p-4 text-sm text-amber-800">
        固定資産を登録するには、有効な部門を1件以上登録してください。
    </div>
@endunless

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <x-label for="asset_code" value="資産コード" />
            <x-input id="asset_code" name="asset_code" type="text" maxlength="32" class="mt-1 block w-full" :value="old('asset_code', $fixedAsset?->asset_code)" required autofocus />
            <x-input-error for="asset_code" class="mt-2" />
        </div>

        <div>
            <x-label for="asset_name" value="資産名" />
            <x-input id="asset_name" name="asset_name" type="text" maxlength="100" class="mt-1 block w-full" :value="old('asset_name', $fixedAsset?->asset_name)" required />
            <x-input-error for="asset_name" class="mt-2" />
        </div>

        <div>
            <x-label for="asset_category" value="資産区分" />
            <select id="asset_category" name="asset_category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">選択してください</option>
                @foreach ($assetCategories as $value => $label)
                    <option value="{{ $value }}" @selected(old('asset_category', $fixedAsset?->asset_category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error for="asset_category" class="mt-2" />
        </div>

        <div>
            <x-label for="asset_category_detail" value="その他区分名（資産区分が「その他」の場合）" />
            <x-input id="asset_category_detail" name="asset_category_detail" type="text" maxlength="100" class="mt-1 block w-full" :value="old('asset_category_detail', $fixedAsset?->asset_category_detail)" />
            <x-input-error for="asset_category_detail" class="mt-2" />
        </div>

        <div>
            <x-label for="department_id" value="部門" />
            <select id="department_id" name="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">選択してください</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((int) old('department_id', $fixedAsset?->department_id) === $department->id)>
                        {{ $department->code }} {{ $department->name }}{{ $department->is_active ? '' : '（無効）' }}
                    </option>
                @endforeach
            </select>
            <x-input-error for="department_id" class="mt-2" />
        </div>

        <div>
            <x-label for="acquisition_date" value="取得日" />
            <x-input id="acquisition_date" name="acquisition_date" type="date" min="1900-01-01" max="9999-12-31" class="mt-1 block w-full" :value="old('acquisition_date', $fixedAsset?->acquisition_date?->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
            <x-input-error for="acquisition_date" class="mt-2" />
        </div>

        <div>
            <x-label for="service_start_date" value="供用開始日" />
            <x-input id="service_start_date" name="service_start_date" type="date" min="1900-01-01" max="9999-12-31" class="mt-1 block w-full" :value="old('service_start_date', $fixedAsset?->service_start_date?->format('Y-m-d'))" />
            <x-input-error for="service_start_date" class="mt-2" />
        </div>

        <div>
            <x-label for="acquisition_cost" value="取得価額" />
            <x-input id="acquisition_cost" name="acquisition_cost" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('acquisition_cost', $fixedAsset?->acquisition_cost)" required />
            <x-input-error for="acquisition_cost" class="mt-2" />
        </div>

        <div>
            <x-label for="useful_life_years" value="耐用年数（年）" />
            <x-input id="useful_life_years" name="useful_life_years" type="number" min="1" max="255" class="mt-1 block w-full" :value="old('useful_life_years', $fixedAsset?->useful_life_years)" />
            <x-input-error for="useful_life_years" class="mt-2" />
        </div>

        <div>
            <x-label for="depreciation_method" value="償却方法" />
            <select id="depreciation_method" name="depreciation_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">選択してください</option>
                @foreach ($depreciationMethods as $value => $label)
                    <option value="{{ $value }}" @selected(old('depreciation_method', $fixedAsset?->depreciation_method ?? 'straight_line') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error for="depreciation_method" class="mt-2" />
        </div>

        <div>
            <x-label for="residual_value" value="残存価額" />
            <x-input id="residual_value" name="residual_value" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('residual_value', $fixedAsset?->residual_value ?? '0')" required />
            <x-input-error for="residual_value" class="mt-2" />
        </div>

        <div>
            <x-label for="current_period_depreciation_expense" value="当期減価償却費" />
            <x-input id="current_period_depreciation_expense" name="current_period_depreciation_expense" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('current_period_depreciation_expense', $fixedAsset?->current_period_depreciation_expense ?? '0')" required />
            <x-input-error for="current_period_depreciation_expense" class="mt-2" />
        </div>

        <div>
            <x-label for="accumulated_depreciation" value="減価償却累計額" />
            <x-input id="accumulated_depreciation" name="accumulated_depreciation" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('accumulated_depreciation', $fixedAsset?->accumulated_depreciation ?? '0')" required />
            <x-input-error for="accumulated_depreciation" class="mt-2" />
        </div>

        <div>
            <x-label for="status" value="資産状態" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $fixedAsset?->status ?? 'held') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error for="status" class="mt-2" />
        </div>
    </div>

    @if ($fixedAsset !== null)
        <div class="rounded-md bg-gray-50 p-4">
            <p class="text-sm text-gray-500">現在の帳簿価額（取得価額 − 減価償却累計額）</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format((float) $fixedAsset->bookValue(), 2) }}</p>
        </div>
    @else
        <p class="text-sm text-gray-500">帳簿価額は、取得価額から減価償却累計額を差し引いて表示します。</p>
    @endif

    <div>
        <x-label for="notes" value="備考" />
        <textarea id="notes" name="notes" rows="4" maxlength="500" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $fixedAsset?->notes) }}</textarea>
        <x-input-error for="notes" class="mt-2" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ $fixedAsset === null ? route('fixed-assets.index') : route('fixed-assets.show', $fixedAsset) }}" class="text-sm text-gray-600 underline hover:text-gray-900">キャンセル</a>
        <x-button :disabled="! $canSubmit">{{ $submitLabel }}</x-button>
    </div>
</form>
