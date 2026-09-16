@props([
    'count',
    'label' => '該当件数',
])

<div
    {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4 rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm']) }}
    aria-label="{{ $label }}：{{ number_format($count) }}件"
>
    <p class="text-base font-semibold text-gray-800">{{ $label }}</p>
    <p class="flex items-baseline gap-1">
        <span class="text-2xl font-bold tabular-nums text-sky-800">{{ number_format($count) }}</span>
        <span class="text-sm font-semibold text-gray-700">件</span>
    </p>
</div>
