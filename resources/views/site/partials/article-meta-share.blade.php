{{--
  Publication metadata and share bar, reused above the image and after the article body.
  Required: $article, $translation, $shareUrl, $readingMins
  Opsiyonel: $bottom (true) - alt bolum icin ust cizgi ve bosluk
--}}
@php
    $isBottom = !empty($bottom);
    $modifiedAt = $modifiedAt ?? $article->publicModifiedAt($translation ?? null);
@endphp
@if($isBottom)
<div class="mt-10 border-t border-stone-200 pt-8 dark:border-stone-700">
@endif
    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm tabular-nums text-stone-500 dark:text-stone-400 sm:text-base {{ $isBottom ? '' : 'mt-4' }}">
        <span>{{ __('site.published_at_label') }}</span>
        <time datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->translatedFormat('d M Y, H:i') }}</time>
        <span aria-hidden="true">&middot;</span>
        <span>{{ __('site.updated_at_label') }}</span>
        <time datetime="{{ $modifiedAt->toIso8601String() }}">{{ $modifiedAt->translatedFormat('d M Y, H:i') }}</time>
        <span aria-hidden="true">&middot;</span>
        <span>{{ __('site.min_read', ['n' => (string) $readingMins]) }}</span>
        @if($article->author)
            <span aria-hidden="true">&middot;</span>
            @if($article->author->profileUrl())
                <a href="{{ $article->author->profileUrl() }}"
                   class="font-medium text-stone-700 hover:text-novara-800 hover:underline dark:text-stone-300">{{ $article->author->name }}</a>
                @if($article->author->title)
                    <span class="text-stone-400">({{ $article->author->title }})</span>
                @endif
            @else
                <span class="font-medium text-stone-700 dark:text-stone-300">{{ $article->author->name }}</span>
            @endif
        @endif
    </p>

    <div class="mt-3 flex flex-wrap items-center gap-2 sm:gap-2.5">
        <span class="shrink-0 text-xs font-semibold uppercase tracking-wide text-stone-400 dark:text-stone-500">{{ __('site.share_article') }}</span>

        <a href="https://twitter.com/intent/tweet?text={{ urlencode($translation->title) }}&url={{ urlencode($shareUrl) }}"
           target="_blank" rel="noopener noreferrer"
           class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-stone-100 text-stone-500 transition hover:bg-black hover:text-white dark:bg-stone-800 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white"
           aria-label="{{ __('site.share_on', ['platform' => 'X']) }}">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.74l7.73-8.835L1.254 2.25H8.08l4.261 5.632L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>

        <a href="https://wa.me/?text={{ urlencode($translation->title.' '.$shareUrl) }}"
           target="_blank" rel="noopener noreferrer"
           class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-stone-100 text-stone-500 transition hover:bg-green-500 hover:text-white dark:bg-stone-800 dark:text-stone-400"
           aria-label="{{ __('site.share_on', ['platform' => 'WhatsApp']) }}">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
        </a>

        <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ urlencode($translation->title) }}"
           target="_blank" rel="noopener noreferrer"
           class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-stone-100 text-stone-500 transition hover:bg-sky-500 hover:text-white dark:bg-stone-800 dark:text-stone-400"
           aria-label="{{ __('site.share_on', ['platform' => 'Telegram']) }}">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
        </a>

        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"
           target="_blank" rel="noopener noreferrer"
           class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-stone-100 text-stone-500 transition hover:bg-blue-600 hover:text-white dark:bg-stone-800 dark:text-stone-400"
           aria-label="{{ __('site.share_on', ['platform' => 'LinkedIn']) }}">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        </a>

        <button
            type="button"
            data-nv-copy
            data-copy-url="{{ e($shareUrl) }}"
            data-label-copy="{{ __('site.copy_link') }}"
            data-label-copied="{{ __('site.link_copied') }}"
            class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-3 py-1.5 text-xs font-medium text-stone-500 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-400 dark:hover:bg-stone-700"
        >
            <svg class="nv-copy-icon-default h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 0 0-5.656 0l-4 4a4 4 0 1 0 5.656 5.656l1.102-1.101m-.758-4.899a4 4 0 0 0 5.656 0l4-4a4 4 0 0 0-5.656-5.656l-1.1 1.1"/></svg>
            <svg class="nv-copy-icon-done hidden h-3.5 w-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span class="nv-copy-label">{{ __('site.copy_link') }}</span>
        </button>

        <button
            type="button"
            data-nv-bookmark
            data-article-url="{{ e($shareUrl) }}"
            data-article-title="{{ $translation->title }}"
            data-article-image="{{ $article->featured_image ? e(Storage::url($article->featured_image)) : '' }}"
            data-label-save="{{ e(__('site.bookmark_save')) }}"
            data-label-saved="{{ e(__('site.bookmark_saved')) }}"
            data-saved="false"
            class="nv-bookmark-inline inline-flex h-8 w-8 items-center justify-center rounded-full text-stone-400 transition hover:bg-stone-100 hover:text-stone-600 dark:hover:bg-stone-800 dark:hover:text-stone-300 sm:ml-auto"
            aria-label="{{ __('site.bookmark_save') }}"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" fill="none">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0z"/>
            </svg>
        </button>
    </div>
@if($isBottom)
</div>
@endif
