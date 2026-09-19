@props([
    'variant' => 'neutral',
    'href' => null,
    'type' => 'button',
])

@php
    $variantClass = match ($variant) {
        'primary' => 'nv-btn-primary',
        'chip' => 'nv-chip-btn',
        'chip-muted' => 'nv-chip-muted-btn',
        'social' => 'nv-social-btn',
        'icon' => 'nv-icon-btn',
        'icon-sm' => 'nv-icon-btn-sm',
        'icon-lg' => 'nv-icon-btn-lg',
        'ticker' => 'nv-ticker-btn',
        'fab' => 'nv-fab-btn',
        'carousel-nav' => 'nv-carousel-nav-btn',
        'carousel-nav-accent' => 'nv-carousel-nav-btn-accent',
        default => 'nv-btn',
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
