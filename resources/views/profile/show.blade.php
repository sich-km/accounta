<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            プロフィール
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                <x-action-section>
                    <x-slot name="title">
                        組織情報
                    </x-slot>

                    <x-slot name="description">
                        現在所属している会社と組織の情報です。
                    </x-slot>

                    <x-slot name="content">
                        <dl class="grid gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">会社名</dt>
                                <dd class="mt-1 text-base font-semibold text-gray-900">{{ $company->name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">企業コード</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $company->code }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">会計年度開始月</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $company->fiscal_year_start_month }}月</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">所属組織</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $organization->name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">ユーザー種別</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ App\Models\User::TYPES[$user->user_type] ?? $user->user_type }}
                                </dd>
                            </div>
                        </dl>
                    </x-slot>
                </x-action-section>
            </div>

            <x-section-border />

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.update-password-form')
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.two-factor-authentication-form')
                </div>

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('profile.delete-user-form')
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
