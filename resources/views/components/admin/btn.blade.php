@props([
    'variant' => 'neutral',
    'href' => null,
    'type' => 'button',
])

@php
    $variantClass = match ($variant) {
        'primary' => 'admin-btn-primary',
        'danger' => 'admin-btn-danger',
        'info' => 'admin-btn-info',
        'success' => 'admin-btn-success',
        'warning' => 'admin-btn-warning',
        'soft-warning' => 'admin-btn-soft-warning',
        'soft-danger' => 'admin-btn-soft-danger',
        default => 'admin-btn',
    };
@endphp

@if(filled($href))
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $variantClass]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $variantClass]) }}>
        {{ $slot }}
    </button>
@endif
