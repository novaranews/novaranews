@php
    $privacyLink = '<a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="underline decoration-dotted underline-offset-2 hover:text-novara-800 dark:hover:text-sky-400">'.e(__('site.recaptcha_privacy_policy')).'</a>';
    $termsLink = '<a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer" class="underline decoration-dotted underline-offset-2 hover:text-novara-800 dark:hover:text-sky-400">'.e(__('site.recaptcha_terms')).'</a>';
@endphp
<p class="text-xs leading-relaxed text-stone-400 dark:text-stone-500">
    {!! __('site.recaptcha_notice_html', ['privacy' => $privacyLink, 'terms' => $termsLink]) !!}
</p>
