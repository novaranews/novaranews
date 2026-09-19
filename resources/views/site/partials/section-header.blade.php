@props([
    'title',
    'url' => null,
    'linkLabel' => null,
    'tone' => 'brand',
    'class' => '',
])

@php
    $toneMap = [
        'brand' => 'bg-novara-800 dark:bg-sky-400',
        'violet' => 'bg-fuchsia-600 dark:bg-fuchsia-400',
        'neutral' => 'bg-stone-500 dark:bg-stone-400',
    ];
    $markClass = $toneMap[$tone] ?? $toneMap['brand'];
@endphp

<div class="{{ trim('mb-3 flex items-center justify-between gap-3 '.$class) }}">
    <h2 class="nv-kicker">
        <span class="nv-kicker-mark {{ $markClass }}"></span>
        {!! htmlspecialchars($title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
    </h2>
    @if($url && $linkLabel)
        <a href="{{ $url }}" class="shrink-0 text-xs font-semibold text-novara-800 transition hover:underline dark:text-sky-400">
            {{ $linkLabel }}
        </a>
    @endif
</div>
