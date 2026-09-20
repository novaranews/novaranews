{{--
  Reusable article card partial.
  Required in scope: $a (Article), $catColor (closure), $authorInitials (closure), $avatarBg (closure), $readTime (closure)
  Optional: $cardSize ('normal' | 'small') - default 'normal'
  Optional: $cardClass - extra classes on the root link/div (e.g. width for horizontal scroll)
  Optional: $cardShowExcerpt (bool) - default true when normal size; set false for denser grids
--}}
@php
    $t      = $a->translate();
    $c      = $a->category?->translate();
    $url    = $a->publicUrl();
    $sz     = $cardSize ?? 'normal';
    $isSmall = $sz === 'small';
    $showExcerpt = ($cardShowExcerpt ?? true) && ! $isSmall && $t->excerpt;
    $editorsPickClass = ($a->is_editors_pick ?? false) ? ' border-l-4 border-l-amber-500' : '';
    $rootClass = trim('nv-surface-card group flex flex-col overflow-hidden'.$editorsPickClass.' '.($cardClass ?? ''));
@endphp
@if($t)
    @if($url)
    <a href="{{ $url }}"
       class="{{ $rootClass }}">
    @else
    <div class="{{ $rootClass }}">
    @endif

        {{-- contain fits the entire image in the box without cropping. --}}
        <div class="relative flex aspect-video shrink-0 items-center justify-center overflow-hidden bg-stone-100 dark:bg-stone-800">
            @if($a->featured_image)
                @php
                    $imgSrc  = Storage::url($a->featured_image);
                    $imgThumb = $a->featured_image_thumb ? Storage::url($a->featured_image_thumb) : null;
                @endphp
                <div class="absolute inset-0 animate-pulse bg-stone-200 dark:bg-stone-700 js-img-skeleton"></div>
                <img
                    src="{{ $imgSrc }}"
                    @if($imgThumb)
                    srcset="{{ $imgThumb }} {{ \App\Services\ImageOptimizerService::THUMB_WIDTH }}w, {{ $imgSrc }} 1920w"
                    sizes="(max-width: 640px) 100vw, (max-width: 1280px) 50vw, 420px"
                    @endif
                    alt="{!! htmlspecialchars($a->featured_image_alt ?: $t->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}"
                    class="relative h-full w-full min-h-0 min-w-0 object-cover"
                    width="800"
                    height="450"
                    loading="lazy"
                    decoding="async"
                    onload="this.previousElementSibling?.remove()"
                >
            @else
                {{-- Placeholder --}}
                <div class="flex h-full items-center justify-center">
                    <svg class="h-10 w-10 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 15l-5-6-4 5-3-3-5 6"/>
                    </svg>
                </div>
            @endif

            {{-- Bookmark (localStorage; app.js data-nv-bookmark) --}}
            @if($url)
            <button
                type="button"
                data-nv-bookmark
                data-article-url="{{ e($url) }}"
                data-article-title="{{ $t->title }}"
                data-article-image="{{ $a->featured_image ? e(Storage::url($a->featured_image)) : '' }}"
                data-label-save="{{ e(__('site.bookmark_save')) }}"
                data-label-saved="{{ e(__('site.bookmark_saved')) }}"
                data-saved="false"
                class="nv-bookmark-card absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-white/80 text-stone-400 shadow-sm transition hover:text-stone-700 dark:bg-stone-900/80 dark:hover:text-stone-200"
                aria-label="{{ __('site.bookmark_save') }}"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" fill="none">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0z"/>
                </svg>
            </button>
            @endif
        </div>

        {{-- Content --}}
        <div class="{{ $isSmall ? 'p-3' : 'p-4' }} flex min-h-0 min-w-0 flex-1 flex-col">
            <div class="flex flex-wrap items-center gap-1.5">
                @include('site.partials.content-type-badge', ['article' => $a])
                @if($c)
                    <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white {{ $catColor($a->category?->key) }}">
                        {{ $c->name }}
                    </span>
                @endif
            </div>

            <h3 class="{{ $isSmall ? 'text-sm' : 'text-base' }} mt-2 font-sans font-semibold leading-snug tracking-tight text-stone-900 line-clamp-2 group-hover:text-novara-800 dark:text-stone-100 dark:group-hover:text-sky-400">
                {!! htmlspecialchars($t->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
            </h3>

            @if($showExcerpt)
                <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-stone-500 dark:text-stone-400">
                    {!! htmlspecialchars($t->excerpt, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                </p>
            @endif

            {{-- Meta bar (dense, tabular numbers for time) --}}
            <div class="{{ $showExcerpt ? 'mt-auto' : 'mt-2' }} flex items-center gap-1.5 pt-3 text-xs tabular-nums text-stone-500 dark:text-stone-400">
                @if($a->author)
                    @if(!empty($a->author->avatar_url))
                        <img
                            src="{{ $a->author->avatar_url }}"
                            alt="{{ $a->author->name }}"
                            class="nv-avatar-sm"
                            width="24"
                            height="24"
                            loading="lazy"
                            decoding="async"
                        >
                    @else
                        <span class="{{ $avatarBg($a->author->name) }} nv-avatar-fallback-sm">
                            {{ $authorInitials($a->author->name) }}
                        </span>
                    @endif
                    <span class="max-w-[70px] truncate font-medium text-stone-500 dark:text-stone-400">
                        {{ $a->author->name }}
                    </span>
                    <span aria-hidden="true" class="text-stone-300 dark:text-stone-600">·</span>
                @endif
                <time datetime="{{ $a->published_at?->toIso8601String() }}">
                    {{ $a->published_at?->diffForHumans() }}
                </time>
                @if($t->body)
                    <span aria-hidden="true" class="text-stone-300 dark:text-stone-600">·</span>
                    <span>{{ __('site.min_read', ['n' => (string) $readTime($t->body)]) }}</span>
                @endif
            </div>
        </div>

    @if($url)</a>@else</div>@endif
@endif
