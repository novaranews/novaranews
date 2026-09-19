@if($article->author && $article->author->hasPublicContact())
    <section class="mt-8 rounded-xl border border-stone-200 bg-stone-50 p-4 shadow-sm dark:border-stone-700 dark:bg-stone-900/40" aria-label="{{ __('site.article_author_card_title') }}">
        <div class="flex items-start gap-4">
            <img src="{{ $article->author->avatar_url }}" alt="{{ $article->author->name }}" class="nv-avatar-md">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">{{ __('site.article_author_card_title') }}</p>
                <p class="mt-1 text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $article->author->name }}</p>
                @if($article->author->profileTitleForLocale())
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ $article->author->profileTitleForLocale() }}</p>
                @endif

                @if($article->author->profileUrl())
                    <a href="{{ $article->author->profileUrl() }}" class="mt-2 inline-flex text-xs font-medium text-novara-800 hover:underline dark:text-sky-400">
                        {{ __('site.article_author_profile_link') }}
                    </a>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    @if($article->author->mailtoPublicEmail())
                        <x-site.btn :href="$article->author->mailtoPublicEmail()" variant="chip" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs hover:bg-white">
                            @include('site.partials.icons.mail')
                            <span>{{ __('site.common_email') }}</span>
                        </x-site.btn>
                    @endif
                    @if($article->author->telUrl())
                        <x-site.btn :href="$article->author->telUrl()" variant="chip" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs hover:bg-white">
                            @include('site.partials.icons.phone')
                            <span>{{ __('site.profile_phone_label') }}</span>
                        </x-site.btn>
                    @endif
                    @if($article->author->whatsappUrl())
                        <x-site.btn :href="$article->author->whatsappUrl()" target="_blank" rel="me noopener noreferrer" variant="chip" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs hover:bg-white">
                            @include('site.partials.icons.whatsapp')
                            <span>{{ $article->author->whatsappDisplayNumber() }}</span>
                        </x-site.btn>
                    @endif
                    @if($article->author->telegramUrl())
                        <x-site.btn :href="$article->author->telegramUrl()" target="_blank" rel="me noopener noreferrer" variant="chip" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs hover:bg-white">
                            @include('site.partials.icons.telegram')
                            <span>{{ '@'.$article->author->telegramDisplayHandle() }}</span>
                        </x-site.btn>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
