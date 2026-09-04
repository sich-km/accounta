<x-form-section submit="updateProfileInformation">
    <x-slot name="title">
        プロフィール
    </x-slot>

    <x-slot name="description">
        ユーザーIDは変更できません。表示名のみ変更できます。
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-label for="login_id" value="ユーザーID" />
            <x-input id="login_id" type="text" class="mt-1 block w-full bg-gray-100" :value="$this->user->login_id" disabled />
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="name" value="表示名" />
            <x-input id="name" type="text" class="mt-1 block w-full" wire:model="state.name" required autocomplete="name" />
            <x-input-error for="name" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            保存しました。
        </x-action-message>

        <x-button wire:loading.attr="disabled">
            保存
        </x-button>
    </x-slot>
</x-form-section>
