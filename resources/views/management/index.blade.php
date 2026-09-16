<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">管理</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('departments.index') }}" class="rounded-lg border border-transparent bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <h3 class="text-lg font-semibold text-gray-900">部門マスタ</h3>
                    <p class="mt-2 text-sm text-gray-600">入力に使用する部門を管理します。</p>
                </a>
                <a href="{{ route('budget-actual-accounts.index') }}" class="rounded-lg border border-transparent bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <h3 class="text-lg font-semibold text-gray-900">予実管理科目マスタ</h3>
                    <p class="mt-2 text-sm text-gray-600">予算・実績で使用する管理科目を管理します。</p>
                </a>
                <a href="{{ route('ledger-accounts.index') }}" class="rounded-lg border border-transparent bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <h3 class="text-lg font-semibold text-gray-900">仕訳用勘定科目マスタ</h3>
                    <p class="mt-2 text-sm text-gray-600">仕訳で使用する勘定科目と通常残高を管理します。</p>
                </a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('companies.index') }}" class="rounded-lg border border-transparent bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <h3 class="text-lg font-semibold text-gray-900">会社情報管理</h3>
                        <p class="mt-2 text-sm text-gray-600">会社情報の登録・編集・削除を行います。</p>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
