@props([
    'variant' => 'generic',
    'articleReadiness' => [],
])

@php
    $variantConfig = config("admin_seo_preview.variants.{$variant}", []);
    $labelMap = $variantConfig['labels'] ?? [];
@endphp

@if($variant === 'article')
    <div class="rounded-md border border-indigo-100 bg-indigo-50/40 p-3"
         data-article-seo-preview
         data-init-has-featured-image="{{ (int) ($articleReadiness['has_featured_image'] ?? 0) }}"
         data-init-featured-image-width="{{ (int) ($articleReadiness['featured_image_width'] ?? 0) }}"
         data-tone-medium-threshold="{{ (int) \App\Models\Setting::get('editorial_guardrail_medium_threshold', config('editorial_guardrail.medium_risk_threshold', 35)) }}"
         data-tone-high-threshold="{{ (int) \App\Models\Setting::get('editorial_guardrail_high_threshold', config('editorial_guardrail.high_risk_threshold', 60)) }}"
         @foreach($labelMap as $dataAttr => $translationKey)
            {{ $dataAttr }}="{{ __($translationKey) }}"
         @endforeach>
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ __('site.admin_seo_preview_title') }}</p>
        <p class="mt-1 text-xs text-gray-500">{{ __('site.admin_seo_preview_hint') }}</p>
        <div class="mt-3 rounded-md border border-gray-200 bg-white p-3 shadow-sm">
            <p class="text-xs font-semibold text-gray-500">{{ __('site.admin_seo_preview_search_label') }}</p>
            <p class="mt-1 line-clamp-2 text-[15px] leading-snug text-blue-700" data-article-preview-title></p>
            <p class="mt-1 text-[11px] text-gray-500" data-article-preview-title-len></p>
            <p class="mt-1 text-[11px] text-gray-500" data-article-preview-title-px></p>
            <div class="mt-1 h-1.5 rounded bg-gray-200">
                <div class="h-1.5 rounded transition-all" data-article-preview-title-bar style="width: 0%"></div>
            </div>
            <p class="mt-1 line-clamp-3 text-xs leading-relaxed text-gray-600" data-article-preview-desc></p>
            <p class="mt-1 text-[11px] text-gray-500" data-article-preview-desc-len></p>
            <p class="mt-1 text-[11px] text-gray-500" data-article-preview-desc-px></p>
            <div class="mt-1 h-1.5 rounded bg-gray-200">
                <div class="h-1.5 rounded transition-all" data-article-preview-desc-bar style="width: 0%"></div>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-600" data-article-preview-ctr></p>
        <div class="mt-3 rounded-md border border-gray-200 bg-white p-3 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold text-gray-500">{{ __('site.admin_article_readiness_title') }}</p>
                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" data-article-readiness-status></span>
            </div>
            <p class="mt-1 text-sm font-semibold text-gray-900" data-article-readiness-score></p>
            <ul class="mt-2 space-y-1 text-xs text-gray-700" data-article-readiness-items></ul>
        </div>
        <div class="mt-3 rounded-md border border-gray-200 bg-white p-3 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold text-gray-500">{{ __('site.admin_tone_guardrail_title') }}</p>
                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" data-article-tone-risk></span>
            </div>
            <p class="mt-1 text-sm font-semibold text-gray-900" data-article-tone-score></p>
            <ul class="mt-2 space-y-1 text-xs text-gray-700" data-article-tone-reasons></ul>
            <ul class="mt-2 list-disc pl-5 text-xs text-gray-700" data-article-tone-suggestions></ul>
        </div>
    </div>
@else
    <div class="rounded-md border border-indigo-100 bg-indigo-50/40 p-3"
         data-seo-preview
         @foreach($labelMap as $dataAttr => $translationKey)
            {{ $dataAttr }}="{{ __($translationKey) }}"
         @endforeach>
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ __('site.admin_seo_preview_title') }}</p>
        <p class="mt-1 text-xs text-gray-500">{{ __('site.admin_seo_preview_hint') }}</p>
        <div class="mt-3 rounded-md border border-gray-200 bg-white p-3 shadow-sm">
            <p class="text-xs font-semibold text-gray-500">{{ __('site.admin_seo_preview_search_label') }}</p>
            <p class="mt-1 line-clamp-2 text-[15px] leading-snug text-blue-700" data-preview-meta-title></p>
            <p class="mt-1 text-[11px] text-gray-500" data-preview-meta-title-len></p>
            <p class="mt-1 text-[11px] text-gray-500" data-preview-meta-title-px></p>
            <div class="mt-1 h-1.5 rounded bg-gray-200">
                <div class="h-1.5 rounded transition-all" data-preview-meta-title-bar style="width: 0%"></div>
            </div>
            <p class="text-xs text-green-700" data-preview-canonical></p>
            <p class="mt-1 line-clamp-3 text-xs leading-relaxed text-gray-600" data-preview-meta-description></p>
            <p class="mt-1 text-[11px] text-gray-500" data-preview-meta-description-len></p>
            <p class="mt-1 text-[11px] text-gray-500" data-preview-meta-description-px></p>
            <div class="mt-1 h-1.5 rounded bg-gray-200">
                <div class="h-1.5 rounded transition-all" data-preview-meta-description-bar style="width: 0%"></div>
            </div>
        </div>
        <div class="mt-3 rounded-md border border-gray-200 bg-white p-3 shadow-sm">
            <p class="text-xs font-semibold text-gray-500">{{ __('site.admin_seo_preview_social_label') }}</p>
            <p class="mt-1 line-clamp-2 text-sm font-semibold text-gray-900" data-preview-og-title></p>
            <p class="mt-1 line-clamp-3 text-xs leading-relaxed text-gray-600" data-preview-og-description></p>
            <p class="mt-1 text-xs text-gray-500" data-preview-robots></p>
            <p class="mt-2 text-xs text-gray-600" data-preview-ctr></p>
        </div>
    </div>
@endif
