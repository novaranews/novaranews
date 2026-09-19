@extends('site.layout')

@section('title', $seo->title)
@section('canonical', $seo->canonical)

@push('hreflang')
    @include('site.partials.hreflang', ['localeSwitchUrls' => $localeSwitchUrls])
@endpush

@push('meta')
    @php
        $desc = $seo->effectiveOgDescription();
        $twitterHandle = $author->twitterHandle();
        $personSchema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            'name'     => $author->name,
            'url'      => $seo->canonical,
            'image'    => $author->avatar_url,
            'worksFor' => [
                '@type' => 'Organization',
                'name'  => \App\Models\Setting::site('site_name', config('app.name')),
                'url'   => url('/'),
            ],
        ];
        $jobTitle = $author->profileTitleForLocale();
        $bioText = $author->profileBioForLocale();
        if ($jobTitle) { $personSchema['jobTitle'] = $jobTitle; }
        if ($bioText) {
            $plainBio = strip_tags(\App\Support\HtmlText::decodeEntitiesForBlade($bioText));
            $personSchema['description'] = \Illuminate\Support\Str::limit($plainBio, 500, '');
        }
        if ($author->mailtoPublicEmail()) { $personSchema['email'] = $author->mailtoPublicEmail(); }
        if ($author->show_phone && $author->phone) { $personSchema['telephone'] = $author->phone; }
        if ($author->show_address && $author->address) { $personSchema['address'] = $author->address; }
        $sameAs = $author->publicSameAs();
        if (!empty($sameAs))   { $personSchema['sameAs'] = $sameAs; }
    @endphp
    <x-site.seo-meta :seo="$seo" :variant="\App\Support\SeoMetaVariant::PROFILE" />
    <meta property="og:type" content="profile">
    <meta property="og:url" content="{{ $seo->canonical }}">
    <meta property="og:title" content="{{ $seo->effectiveOgTitle() }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:image" content="{{ $author->avatar_url }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $author->name }}">
    <meta name="twitter:description" content="{{ $desc }}">
    <meta name="twitter:image" content="{{ $author->avatar_url }}">
    @if($twitterHandle)
        <meta name="twitter:creator" content="{{ '@'.$twitterHandle }}">
    @endif
    <script type="application/ld+json">
        {!! json_encode($personSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
<div class="nv-container max-w-4xl py-8 sm:py-10">

    {{-- Author card --}}
    <div class="flex flex-col items-center gap-6 rounded-2xl border border-stone-200 bg-white p-8 shadow-sm sm:flex-row sm:items-start dark:border-stone-700 dark:bg-stone-900">
        {{-- Avatar --}}
        <img src="{{ $author->avatar_url }}"
             alt="{{ $author->name }}"
             class="nv-avatar-xl p-1">

        {{-- Info --}}
        <div class="flex-1 text-center sm:text-left">
            <h1 class="font-serif text-3xl font-bold tracking-tight text-stone-900 dark:text-stone-100">{!! htmlspecialchars($author->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</h1>

            @if($author->profileTitleForLocale())
                <p class="mt-1 text-sm font-medium text-novara-800 dark:text-sky-400">{!! htmlspecialchars($author->profileTitleForLocale(), ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
            @endif

            @if($author->profileBioHtmlForLocale())
                <div class="nv-static-prose mt-3 max-w-none text-sm leading-relaxed">
                    {!! $author->profileBioHtmlForLocale() !!}
                </div>
            @endif

            {{-- Social links --}}
            @if($author->hasPublicContact())
                <div class="mt-4 flex items-center justify-center gap-3 sm:justify-start">
                    @if($author->twitterUrl() && $twitterHandle)
                        <x-site.btn :href="$author->twitterUrl()"
                           target="_blank" rel="me noopener noreferrer"
                           variant="chip-muted">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.263 5.634 5.901-5.634Zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            {{ '@'.$twitterHandle }}
                        </x-site.btn>
                    @endif
                    @if($author->linkedInUrl())
                        <x-site.btn :href="$author->linkedInUrl()"
                           target="_blank" rel="me noopener noreferrer"
                           variant="chip-muted">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                            {{ $author->linkedin }}
                        </x-site.btn>
                    @endif
                    @if($author->telegramUrl())
                        <x-site.btn :href="$author->telegramUrl()"
                           target="_blank" rel="me noopener noreferrer"
                           variant="chip-muted"
                           class="inline-flex items-center gap-1.5">
                            @include('site.partials.icons.telegram')
                            <span>{{ '@'.$author->telegramDisplayHandle() }}</span>
                        </x-site.btn>
                    @endif
                    @if($author->whatsappUrl())
                        <x-site.btn :href="$author->whatsappUrl()"
                           target="_blank" rel="me noopener noreferrer"
                           variant="chip-muted"
                           class="inline-flex items-center gap-1.5">
                            @include('site.partials.icons.whatsapp')
                            <span>{{ $author->whatsappDisplayNumber() }}</span>
                        </x-site.btn>
                    @endif
                </div>
            @endif

            @if($author->mailtoPublicEmail() || $author->telUrl() || ($author->show_address && $author->address))
                <div class="mt-4 space-y-1 text-xs text-stone-500 dark:text-stone-400">
                    @if($author->mailtoPublicEmail())
                        <p class="inline-flex items-center gap-1.5">
                            @include('site.partials.icons.mail')
                            <span>{{ __('site.common_email') }}:</span>
                            <a href="{{ $author->mailtoPublicEmail() }}" class="font-medium hover:underline">{{ $author->public_email }}</a>
                        </p>
                    @endif
                    @if($author->telUrl())
                        <p class="inline-flex items-center gap-1.5">
                            @include('site.partials.icons.phone')
                            <span>{{ __('site.profile_phone_label') }}:</span>
                            <a href="{{ $author->telUrl() }}" class="font-medium hover:underline">{{ $author->phone }}</a>
                        </p>
                    @endif
                    @if($author->show_address && $author->address)
                        <p>{{ __('site.profile_address_label') }}: {!! htmlspecialchars($author->address, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Articles by this author --}}
    <h2 class="mt-10 mb-5 border-b border-stone-200 pb-2 font-serif text-xl font-bold text-stone-800 dark:border-stone-700 dark:text-stone-100">
        {{ __('site.author_articles', ['name' => $author->name]) }}
        <span class="ml-2 text-sm font-normal text-stone-400">({{ $articles->total() }})</span>
    </h2>

    @if($articles->isEmpty())
        <p class="text-sm text-stone-500 dark:text-stone-500">{{ __('site.author_no_articles') }}</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($articles as $item)
                @php
                    $tr  = $item->translate();
                    $cat = $item->category?->translate();
                    $url = $item->publicUrl() ?? '#';
                @endphp
                <a href="{{ $url }}"
                   class="nv-surface-card group flex gap-3 p-4">
                    @if($item->featured_image)
                        <div class="flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-100 dark:bg-stone-800">
                            @include('site.partials.article-img', [
                                'imgArticle' => $item,
                                'imgAlt'     => htmlspecialchars($item->featured_image_alt ?: ($tr?->title ?? ''), ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                                'imgSizes'   => '96px',
                            ])
                        </div>
                    @endif
                    <div class="min-w-0">
                        @if($cat)
                            <span class="text-xs font-medium uppercase tracking-wide text-novara-800 dark:text-sky-400">{!! htmlspecialchars($cat->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</span>
                        @endif
                        <h3 class="mt-0.5 line-clamp-2 text-sm font-semibold leading-snug text-stone-800 group-hover:text-novara-800 dark:text-stone-100 dark:group-hover:text-sky-400">
                            {!! htmlspecialchars($tr?->title ?? '-', ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}
                        </h3>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">{{ $item->published_at?->translatedFormat('d M Y') }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $articles->links() }}
        </div>
    @endif

</div>
@endsection
