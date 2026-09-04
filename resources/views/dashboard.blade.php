<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ダッシュボード
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                <h1 class="text-2xl font-semibold text-gray-900">Accounta</h1>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500">ログインユーザー</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ auth()->user()->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">組織</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $organization->name }}</dd>
                    </div>
                </dl>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <a href="{{ route('amounts.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <h2 class="text-lg font-semibold text-gray-900">予算・実績</h2>
                    <p class="mt-2 text-sm text-gray-600">月次の予算・実績明細を登録します。</p>
                </a>
                <a href="{{ route('departments.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <h2 class="text-lg font-semibold text-gray-900">部門マスタ</h2>
                    <p class="mt-2 text-sm text-gray-600">入力に使用する部門を管理します。</p>
                </a>
                <a href="{{ route('accounts.index') }}" class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md">
                    <h2 class="text-lg font-semibold text-gray-900">勘定科目マスタ</h2>
                    <p class="mt-2 text-sm text-gray-600">入力に使用する勘定科目を管理します。</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
