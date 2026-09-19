@php
    $rawSources  = is_array($translation->sources) ? $translation->sources : [];
    $publications = collect($rawSources)
        ->map(fn($s) => is_string($s) ? $s : ($s['title'] ?? ($s['label'] ?? null)))
        ->filter()
        ->unique()
        ->values()
        ->all();
    $authorName  = $article->author?->name ?: config('app.name');
    $authorUrl   = $article->author?->profileUrl();
@endphp

<aside class="mt-10 rounded-xl border border-stone-200 bg-stone-50 px-5 py-4 text-sm text-stone-600 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-400">
    <div class="flex items-start gap-3">
        {{-- Icon --}}
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-novara-800 dark:text-novara-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
        </svg>

        <div class="space-y-1 leading-relaxed">
            {{-- Main disclosure line --}}
            <p>
                {{ __('site.ai_disclosure_text', ['editor' => $authorName]) }}
                @if($authorUrl)
                    - <a href="{{ $authorUrl }}" class="font-medium text-novara-800 underline decoration-dotted underline-offset-2 hover:no-underline dark:text-novara-400">{!! htmlspecialchars($authorName, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</a>
                @else
                    - <strong class="font-medium text-stone-700 dark:text-stone-300">{!! htmlspecialchars($authorName, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</strong>
                @endif
            </p>

            {{-- Source publications --}}
            @if(!empty($publications))
                <p class="text-xs text-stone-500 dark:text-stone-500">
                    {{ __('site.ai_disclosure_sources') }}:
                    <span class="font-medium text-stone-600 dark:text-stone-400">{!! htmlspecialchars(implode(', ', $publications), ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</span>
                </p>
            @endif
        </div>
    </div>
</aside>
