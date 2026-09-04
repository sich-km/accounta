<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="login_id" value="ユーザーID" />
                <x-input id="login_id" class="mt-1 block w-full" type="text" name="login_id" :value="old('login_id')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="パスワード" />
                <x-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">ログイン状態を保持する</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="ms-4">
                    ログイン
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
