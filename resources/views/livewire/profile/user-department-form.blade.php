<form wire:submit="updateDepartment" class="mt-8 border-t border-gray-200 pt-6">
    <div class="max-w-xl">
        <x-label for="department_id" value="所属部門" />
        <select id="department_id" wire:model="departmentId" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">未設定</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}">
                    {{ $department->code }} {{ $department->name }}{{ $department->is_active ? '' : '（無効）' }}
                </option>
            @endforeach
        </select>
        <x-input-error for="departmentId" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">仕訳を新規登録するときの起票部門の初期値として使用します。</p>
    </div>

    <div class="mt-4 flex items-center gap-3">
        <x-button type="submit" wire:loading.attr="disabled" wire:target="updateDepartment">
            保存
        </x-button>
        <x-action-message on="department-saved">
            保存しました。
        </x-action-message>
    </div>
</form>
