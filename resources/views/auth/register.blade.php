<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <x-label for="login_id" value="ユーザーID" />
                <x-input id="login_id" class="mt-1 block w-full" type="text" name="login_id" :value="old('login_id')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="name" value="表示名" />
                <x-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            </div>

            <div class="mt-4">
                <x-label for="organization_name" value="組織名" />
                <x-input id="organization_name" class="mt-1 block w-full" type="text" name="organization_name" :value="old('organization_name')" required />
            </div>

            <div class="mt-4">
                <x-label for="department_code" value="所属部門コード（任意）" />
                <x-input id="department_code" class="mt-1 block w-full" type="text" name="department_code" :value="old('department_code')" />
            </div>

            <div class="mt-4">
                <x-label for="department_name" value="所属部門名（任意）" />
                <x-input id="department_name" class="mt-1 block w-full" type="text" name="department_name" :value="old('department_name')" />
                <p class="mt-1 text-xs text-gray-500">入力した場合、この組織の最初の部門として登録されます。</p>
            </div>

            <div class="mt-4">
                <x-label for="password" value="パスワード" />
                <x-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="パスワード確認" />
                <x-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                    登録済みの方
                </a>

                <x-button class="ms-4">
                    登録
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
