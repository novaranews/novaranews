@extends('site.layout')

@section('canonical', $seo->canonical)
@section('title', $seo->title)

@php
    $articleModifiedAt = $article->publicModifiedAt($translation);
@endphp

@push('hreflang')
    @include('site.partials.hreflang', ['localeSwitchUrls' => $localeSwitchUrls])
@endpush

@push('meta')
    <x-site.seo-meta :seo="$seo" :variant="\App\Support\SeoMetaVariant::ARTICLE" />
    @if($seo->ogImageUrl)
        @if($seo->extra('featuredDims'))
            <meta property="og:image:width" content="{{ $seo->extra('featuredDims')['width'] }}">
            <meta property="og:image:height" content="{{ $seo->extra('featuredDims')['height'] }}">
        @endif
        <meta property="og:image:alt" content="{!! htmlspecialchars($article->featured_image_alt ?: $translation->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}">
    @endif
    @if($article->published_at)
        <meta property="article:published_time" content="{{ $article->published_at->toAtomString() }}">
    @endif
    <meta property="article:modified_time" content="{{ $articleModifiedAt->toAtomString() }}">
    @if($seo->extra('articleSection'))
        <meta property="article:section" content="{{ $seo->extra('articleSection') }}">
    @endif
    @if($seo->extra('authorProfileUrl'))
        <meta property="article:author" content="{{ $seo->extra('authorProfileUrl') }}">
    @endif
    <script type="application/ld+json">
        {!! json_encode($seo->schema('article'), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode($seo->schema('breadcrumb'), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
    @include('site.partials.swg')
@endpush

@section('content')
@php $catTr = $article->category?->translate(); @endphp
<div class="nv-container py-8 sm:py-10">
    @if(!empty($preview))
        <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-sm">
            {{ __('site.preview_notice') }}
            @if($article->status === 'published' && $article->published_at && ! $article->published_at->isFuture())
                @if($liveUrl = $article->publicUrl())
                    <a href="{{ $liveUrl }}" class="font-semibold underline">{{ __('site.preview_open_live') }}</a>
                @else
                    <span class="text-amber-900/90">{{ __('site.public_link_requires_slugs') }}</span>
                    <p class="mt-2 text-xs text-amber-800/80">{{ __('site.article_url_structure_note') }}</p>
                @endif
            @else
                <span class="text-amber-800/90">{{ __('site.preview_not_public') }}</span>
            @endif
        </div>
    @endif
    @auth
        @if(auth()->user()->is_admin)
            <div class="mx-auto mb-4 flex max-w-3xl items-center justify-between rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm text-indigo-800 shadow-sm">
                <span class="font-medium">{{ __('site.admin') }}</span>
                <a href="{{ route('admin.articles.edit', $article) }}" class="rounded bg-indigo-700 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-800">
                    ✏ {{ __('site.admin_edit_article') }}
                </a>
            </div>
        @endif
    @endauth

    {{-- Masaüstü yapıcı düzen: article + sticky sosyal sidebar --}}
    <div class="relative mx-auto max-w-3xl xl:max-w-none xl:grid xl:grid-cols-[1fr_48px] xl:gap-6 2xl:grid-cols-[1fr_56px]">

    {{-- Sabit sosyal paylaşım çubuğu (sadece masaüstünde, xl ve üzeri) --}}
    <aside
        class="hidden xl:flex xl:flex-col xl:items-center xl:gap-3 xl:sticky xl:top-24 xl:self-start xl:h-fit xl:pt-16 xl:col-start-2 xl:row-start-1"
        aria-label="{{ __('site.share_article') }}"
    >
        @php $shareUrl = $article->publicUrl() ?? url()->current(); $shareTitle = $translation->title; @endphp
        {{-- X / Twitter --}}
        <a href="https://x.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareTitle) }}"
           target="_blank" rel="noopener noreferrer"
           class="group flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-500 shadow-sm transition hover:border-stone-900 hover:bg-stone-900 hover:text-white dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400 dark:hover:border-stone-900 dark:hover:bg-stone-900 dark:hover:text-white"
           aria-label="{{ __('site.share_on', ['platform' => 'X']) }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        {{-- WhatsApp --}}
        <a href="https://wa.me/?text={{ urlencode($shareTitle.' '.$shareUrl) }}"
           target="_blank" rel="noopener noreferrer"
           class="group flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-500 shadow-sm transition hover:border-green-600 hover:bg-green-600 hover:text-white dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400 dark:hover:border-green-600 dark:hover:bg-green-600 dark:hover:text-white"
           aria-label="{{ __('site.share_on', ['platform' => 'WhatsApp']) }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
        {{-- LinkedIn --}}
        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"
           target="_blank" rel="noopener noreferrer"
           class="group flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-500 shadow-sm transition hover:border-blue-600 hover:bg-blue-600 hover:text-white dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400 dark:hover:border-blue-600 dark:hover:bg-blue-600 dark:hover:text-white"
           aria-label="{{ __('site.share_on', ['platform' => 'LinkedIn']) }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        </a>
        {{-- Kopyala --}}
        <button
            type="button"
            data-nv-copy
            data-copy-url="{{ e($shareUrl) }}"
            data-label-copy="{{ __('site.copy_link') }}"
            data-label-copied="{{ __('site.link_copied') }}"
            title="{{ __('site.copy_link') }}"
            class="group flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-500 shadow-sm transition hover:border-stone-400 hover:bg-stone-100 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:bg-stone-700 nv-copy-sidebar-btn"
            aria-label="{{ __('site.copy_link') }}">
            <svg class="nv-copy-icon-default h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <svg class="nv-copy-icon-done hidden h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
    </aside>

    <article class="mx-auto max-w-3xl xl:max-w-none xl:col-start-1 xl:row-start-1">
        @if($catTr)
            <nav aria-label="{{ __('site.breadcrumb') }}" class="mb-4 text-sm text-stone-500 dark:text-stone-400">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <li><a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="hover:text-novara-800 hover:underline">{{ __('site.home_breadcrumb') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $catTr->slug]) }}" class="hover:text-novara-800 hover:underline">{!! htmlspecialchars($catTr->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-stone-700" aria-current="page">{!! htmlspecialchars($translation->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</li>
                </ol>
            </nav>
            <a href="{{ route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $catTr->slug]) }}" class="text-sm font-bold uppercase tracking-wide text-novara-800 hover:underline dark:text-sky-400">{!! htmlspecialchars($catTr->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</a>
        @endif
        <div class="mt-2 flex flex-wrap items-center gap-2">
            @include('site.partials.content-type-badge', ['article' => $article])
            @if($article->is_editors_pick)
                <span class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900 dark:border-amber-500/50 dark:bg-amber-950/40 dark:text-amber-200">{!! htmlspecialchars(__('site.editors_pick_badge'), ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</span>
            @endif
        </div>
        <h1 class="mt-3 font-serif text-3xl font-bold leading-tight tracking-tight text-stone-900 sm:text-4xl lg:text-[2.65rem] dark:text-stone-100">{!! htmlspecialchars($translation->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</h1>

        @php
            $wordCount   = \App\Support\ArticleContentRules::wordCount($translation->body ?? '');
            $readingMins = max(1, (int) ceil($wordCount / 220));
            $shareUrl = $article->publicUrl() ?? url()->current();
        @endphp

        @include('site.partials.article-meta-share', [
            'article' => $article,
            'translation' => $translation,
            'shareUrl' => $shareUrl,
            'readingMins' => $readingMins,
            'modifiedAt' => $articleModifiedAt,
        ])

        @if($article->featured_image)
            <figure class="mt-6">
                <div class="flex min-h-[12rem] w-full max-h-[min(85vh,900px)] items-center justify-center overflow-hidden rounded-xl bg-stone-200 dark:bg-stone-800 sm:min-h-[16rem]">
                    @php $featuredDims = $seo->extra('featuredDims'); @endphp
                    @include('site.partials.article-img', [
                        'imgArticle'  => $article,
                        'imgAlt'      => htmlspecialchars($article->featured_image_alt ?: $translation->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                        'imgClass'    => 'max-h-[min(85vh,900px)] w-full max-w-full object-cover',
                        'imgLoading'  => 'eager',
                        'imgSizes'    => '(max-width: 768px) 100vw, 800px',
                        'imgWidth'    => $featuredDims['width']  ?? null,
                        'imgHeight'   => $featuredDims['height'] ?? null,
                    ])
                </div>
                @if($article->featured_image_caption)
                    <figcaption class="mt-2 text-center text-sm text-stone-600 dark:text-stone-400">{!! htmlspecialchars($article->featured_image_caption, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</figcaption>
                @endif
            </figure>
        @endif

        @if($translation->excerpt)
            <p class="mt-6 border-l-2 border-novara-300 pl-4 text-lg leading-relaxed text-stone-600 dark:border-sky-600 dark:text-stone-300">{!! htmlspecialchars($translation->excerpt, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
        @endif

        {{-- Table of contents (auto-generated from h2 headings) --}}
        @php
            preg_match_all('/<h2[^>]*>(.*?)<\/h2>/si', $translation->body ?? '', $h2Matches);
            $tocHeadings = collect($h2Matches[1] ?? [])->map(fn($h) => strip_tags($h))->filter()->values();
        @endphp
        @if($tocHeadings->count() >= 2)
        <nav
            class="my-6 rounded-lg border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-800/50"
            aria-label="{{ __('site.table_of_contents') }}"
            data-nv-toc
        >
            <button
                type="button"
                data-nv-toc-toggle
                class="flex w-full items-center justify-between text-sm font-semibold text-stone-800 dark:text-stone-200"
                aria-expanded="true"
            >
                <span>📋 {{ __('site.table_of_contents') }}</span>
                <svg data-nv-toc-icon class="h-4 w-4 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <ol data-nv-toc-panel class="mt-3 space-y-1.5 text-sm">
                @foreach($tocHeadings as $i => $heading)
                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-novara-800/10 text-[10px] font-bold text-novara-800 dark:bg-sky-400/10 dark:text-sky-400">{{ $i + 1 }}</span>
                        <span class="text-stone-700 dark:text-stone-300">{{ $heading }}</span>
                    </li>
                @endforeach
            </ol>
        </nav>
        @endif

        <div class="article-body tech-article-body mx-auto mt-8 max-w-prose text-lg leading-relaxed text-stone-800 dark:text-stone-200
            [&_p]:mt-0 [&_p+p]:mt-5
            [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-stone-900 [&_h2]:leading-snug dark:[&_h2]:text-stone-100
            [&_h3]:mt-6 [&_h3]:mb-2 [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:text-stone-900 dark:[&_h3]:text-stone-100
            [&_h4]:mt-5 [&_h4]:mb-2 [&_h4]:text-lg [&_h4]:font-semibold [&_h4]:text-stone-900 dark:[&_h4]:text-stone-100
            [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:space-y-1.5
            [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:space-y-1.5
            [&_li]:text-stone-700 dark:[&_li]:text-stone-300
            [&_blockquote]:my-6 [&_blockquote]:border-l-4 [&_blockquote]:border-novara-800 [&_blockquote]:pl-5 [&_blockquote]:italic [&_blockquote]:text-stone-600 dark:[&_blockquote]:border-sky-500 dark:[&_blockquote]:text-stone-400
            [&_strong]:font-semibold [&_strong]:text-stone-900 dark:[&_strong]:text-stone-100
            [&_em]:italic
            [&_a]:text-novara-800 [&_a]:underline [&_a]:underline-offset-2 [&_a:hover]:text-novara-900 dark:[&_a]:text-sky-400 dark:[&_a:hover]:text-sky-300
            [&_img]:my-4 [&_img]:h-auto [&_img]:max-w-full [&_img]:object-contain">
            {!! $translation->body !!}
        </div>

        @php
            $sourceEntries = \App\Support\ArticleSources::normalize($translation->sources ?? []);
        @endphp
        {{-- In-article ad: after body, before sources --}}
        @include('site.partials.adsense-slot', ['slotType' => 'in-article'])

        @if(!empty($sourceEntries))
            <section class="mt-8 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-900/40">
                <p class="text-sm font-semibold text-stone-800 dark:text-stone-100">{{ __('site.source_references') }}</p>
                <ul class="mt-2 space-y-1.5 text-sm text-stone-600 dark:text-stone-300">
                    @foreach($sourceEntries as $source)
                        <li>
                            @if($source['url'])
                                <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-novara-800 underline decoration-dotted underline-offset-2 hover:no-underline dark:text-sky-400">
                                    {!! htmlspecialchars($source['title'], ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                                </a>
                            @else
                                <span>{!! htmlspecialchars($source['title'], ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</span>
                            @endif
                            @if($source['claim_note'])
                                <span class="text-stone-500 dark:text-stone-400">- {!! htmlspecialchars($source['claim_note'], ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($article->is_ai_generated)
            @include('site.partials.ai-disclosure', ['article' => $article, 'translation' => $translation])
        @endif

        @include('site.partials.article-author-card', ['article' => $article])

        @include('site.partials.article-meta-share', [
            'article' => $article,
            'translation' => $translation,
            'shareUrl' => $shareUrl,
            'readingMins' => $readingMins,
            'modifiedAt' => $articleModifiedAt,
            'bottom' => true,
        ])


        {{-- Makale sonu tamamlama bildirimi --}}
        <div
            id="nv-article-read-banner"
            hidden
            class="mt-8 rounded-xl border border-green-200 bg-green-50 p-5 text-center transition duration-500 dark:border-green-900/50 dark:bg-green-950/30"
        >
            <p class="text-2xl">✅</p>
            <p class="mt-1 font-semibold text-green-800 dark:text-green-300">{!! htmlspecialchars(__('site.article_read_done'), ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
            <p class="mt-1 text-sm text-green-700 dark:text-green-400">{{ __('site.article_read_browse') }}</p>
            @if($catTr)
            <a href="{{ route('category.show', ['locale' => app()->getLocale(), 'categoryPrefix' => category_path_segment(), 'slug' => $catTr->slug]) }}"
               class="mt-3 inline-block rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-800 dark:bg-green-700 dark:hover:bg-green-600">
                {{ __('site.article_see_category', ['category' => $catTr->name]) }}
            </a>
            @endif
        </div>

    </article>
    </div>{{-- end xl:grid wrapper --}}

    {{-- Yorumlar --}}
    @include('site.partials.comments', ['article' => $article, 'comments' => $comments ?? collect()])

    @php
        $relatedList = $related->filter(fn ($r) => $r->translate() && $r->publicUrl())->values();
    @endphp
    @if($relatedList->isNotEmpty())
        <section class="mx-auto mt-10 max-w-3xl border-t border-stone-200 pt-6 dark:border-stone-700" aria-labelledby="related-articles-heading">
            <div class="rounded-xl border border-stone-200 bg-stone-50/80 p-4 dark:border-stone-700/60 dark:bg-stone-900/40">
                <div id="related-articles-heading">
                    @include('site.partials.section-header', ['title' => __('site.related_articles')])
                </div>
                <div class="relative">
                    @if($relatedList->count() > 1)
                        <x-site.btn
                            type="button"
                            variant="carousel-nav"
                            class="left-0 sm:left-0.5"
                            data-nv-hscroll="nv-strip-related"
                            data-nv-hscroll-dir="-1"
                            aria-label="{{ __('site.card_strip_scroll_prev') }}"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </x-site.btn>
                        <x-site.btn
                            type="button"
                            variant="carousel-nav"
                            class="right-0 sm:right-0.5"
                            data-nv-hscroll="nv-strip-related"
                            data-nv-hscroll-dir="1"
                            aria-label="{{ __('site.card_strip_scroll_next') }}"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </x-site.btn>
                    @endif
                    <div
                        id="nv-strip-related"
                        class="w-full min-w-0 overflow-x-auto scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        tabindex="0"
                        role="region"
                        aria-label="{{ __('site.related_articles') }}"
                    >
                    <ul class="flex w-max min-w-full justify-start gap-4 pr-2 snap-x snap-mandatory">
                        @foreach($relatedList as $r)
                            @php
                                $rt = $r->translate();
                                $rc = $r->category?->translate();
                                $rUrl = $r->publicUrl();
                            @endphp
                            <li class="w-[min(85vw,240px)] shrink-0 snap-start">
                                <a href="{{ $rUrl }}" class="group flex h-full flex-col overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm transition hover:border-stone-300 hover:shadow-md dark:border-stone-700 dark:bg-stone-900 dark:hover:border-stone-600">
                                    <div class="flex aspect-[5/3] items-center justify-center overflow-hidden bg-stone-100 dark:bg-stone-800">
                                        @if($r->featured_image)
                                            @include('site.partials.article-img', [
                                                'imgArticle' => $r,
                                                'imgAlt'     => htmlspecialchars($r->featured_image_alt ?: $rt->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                                                'imgSizes'   => '240px',
                                            ])
                                        @else
                                            <svg class="h-8 w-8 text-stone-300 dark:text-stone-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M21 15l-5-6-4 5-3-3-5 6"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex flex-1 flex-col p-3">
                                        @if($rc)
                                            <span class="text-[10px] font-bold uppercase tracking-wide text-novara-800 dark:text-sky-400">{{ $rc->name }}</span>
                                        @endif
                                        <p class="mt-1 line-clamp-3 text-sm font-sans font-semibold leading-snug tracking-tight text-stone-900 group-hover:text-novara-800 dark:text-stone-100 dark:group-hover:text-sky-400">{!! htmlspecialchars($rt->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                                        <time class="mt-auto pt-2 text-[11px] text-stone-400 dark:text-stone-500" datetime="{{ $r->published_at?->toIso8601String() }}">{{ $r->published_at?->diffForHumans() }}</time>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if(isset($moreInCategory) && $moreInCategory->isNotEmpty() && $catTr)
        <section class="mx-auto mt-10 max-w-3xl border-t border-stone-200 pt-8 dark:border-stone-700">
            <h2 class="font-serif text-lg font-bold text-stone-900 dark:text-stone-100">{{ __('site.more_in_category', ['category' => $catTr->name]) }}</h2>
            <div class="mt-4 divide-y divide-stone-100 dark:divide-stone-800">
                @foreach($moreInCategory as $r)
                    @php $rt = $r->translate(); $rUrl = $r->publicUrl(); @endphp
                    @if($rt && $rUrl)
                        <a href="{{ $rUrl }}" class="flex items-start gap-3 py-3 group">
                            @if($r->featured_image)
                                <div class="mt-0.5 flex h-14 w-20 shrink-0 items-center justify-center overflow-hidden rounded bg-stone-100 dark:bg-stone-800">
                                    @include('site.partials.article-img', [
                                        'imgArticle' => $r,
                                        'imgAlt'     => htmlspecialchars($r->featured_image_alt ?: $rt->title, ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                                        'imgSizes'   => '80px',
                                    ])
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-medium leading-snug text-stone-800 line-clamp-2 group-hover:text-novara-800 dark:text-stone-200 dark:group-hover:text-sky-400">{!! htmlspecialchars($rt->title, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                                <p class="mt-0.5 text-xs text-stone-400 dark:text-stone-500">{{ $r->published_at?->diffForHumans() }}</p>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    {{-- Display ad: bottom of article page --}}
    @include('site.partials.adsense-slot', ['slotType' => 'display'])
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.3.1/dist/css/glightbox.min.css">
<script src="https://cdn.jsdelivr.net/npm/glightbox@3.3.1/dist/js/glightbox.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.article-body img').forEach(function (img) {
        if (img.closest('a')) return;
        const a = document.createElement('a');
        a.href = img.src;
        a.className = 'glightbox';
        a.setAttribute('data-type', 'image');
        a.setAttribute('data-alt', img.alt || '');
        a.style.cursor = 'zoom-in';
        img.parentNode.insertBefore(a, img);
        a.appendChild(img);
    });
    if (typeof GLightbox !== 'undefined') {
        GLightbox({ selector: '.glightbox', touchNavigation: true, loop: false });
    }
});
</script>
@endpush
