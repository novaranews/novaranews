@props([
    'type' => 'db',
])

@php
    $isEnv = strtolower((string) $type) === 'env';
    $label = $isEnv ? 'ENV' : 'DB';
    $classes = $isEnv
        ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
        : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {$classes}"]) }}>
    {{ $label }}
</span>
