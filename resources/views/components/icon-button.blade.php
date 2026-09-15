@props(['href' => null, 'label'])

@php
$classes = 'inline-flex size-8 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1';
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class([$classes])->merge(['type' => 'button']) }}>
        {{ $slot }}
    </button>
@endif
