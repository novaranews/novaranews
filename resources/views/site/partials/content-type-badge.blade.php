{{-- Content-type pill. Required: $article. Optional: $variant - "default" | "hero" (on dark hero image). --}}
@php
    $variant = $variant ?? 'default';
    $key = $article->contentTypeKey();
    $baseClass = $variant === 'hero'
        ? 'inline-flex shrink-0 items-center rounded-full border border-white/30 bg-white/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white backdrop-blur-sm'
        : 'inline-flex shrink-0 items-center rounded-full border border-stone-300 bg-stone-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-stone-700 dark:border-stone-600 dark:bg-stone-700 dark:text-stone-100';
@endphp
<span class="{{ $baseClass }}">
    {{ __('site.content_type_'.$key) }}
</span>
