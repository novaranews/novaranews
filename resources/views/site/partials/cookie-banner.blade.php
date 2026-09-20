{{-- ── Cookie Consent Banner ─────────────────────────────────────────────────
     localStorage key : novara_consent  →  'all' | 'essential'
     'all'       → load GA4 and personalized AdSense
     'essential' → skip GA4 and load non-personalized AdSense with npa=1
     Ads are shown in both cases; only the targeting mode changes.
──────────────────────────────────────────────────────────────────────────── --}}
<div
    id="nv-cookie-banner"
    hidden
    role="dialog"
    aria-modal="false"
    aria-label="{{ __('site.cookie_banner_label') }}"
    class="fixed bottom-0 left-0 right-0 z-[70] border-t border-stone-200 bg-white/95 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] backdrop-blur-sm dark:border-stone-700 dark:bg-stone-900/95"
>
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm leading-relaxed text-stone-600 dark:text-stone-400">
                🍪 {{ __('site.cookie_banner_text') }}
                <a
                    href="{{ route('page.cookies', ['locale' => app()->getLocale()]) }}"
                    class="ml-1 font-medium text-novara-800 underline underline-offset-2 transition hover:text-novara-700 dark:text-sky-400 dark:hover:text-sky-300"
                >{{ __('site.cookie_banner_learn_more') }}</a>
            </p>
            <div class="flex shrink-0 gap-2">
                <button
                    id="nv-cookie-essential"
                    type="button"
                    class="rounded-lg border border-stone-300 bg-transparent px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-800"
                >{{ __('site.cookie_essential_only') }}</button>
                <button
                    id="nv-cookie-accept"
                    type="button"
                    class="rounded-lg bg-novara-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-novara-700 dark:bg-sky-600 dark:hover:bg-sky-500"
                >{{ __('site.cookie_accept_all') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var CONSENT_KEY = 'novara_consent';
    var consent     = localStorage.getItem(CONSENT_KEY);

    /** Load GA4 and/or AdSense according to the consent type. */
    function loadThirdParty(type) {
        if (type === 'all' && typeof window._loadGA4 === 'function') {
            window._loadGA4();
        }
        // Load AdSense in both modes: personalized for all, npa=1 otherwise.
        if (typeof window._setAdSenseConsent === 'function') {
            window._setAdSenseConsent(type);
        }
    }

    /** Save the choice, hide the banner, and load scripts. */
    function setConsent(value) {
        try { localStorage.setItem(CONSENT_KEY, value); } catch (e) {}
        var banner = document.getElementById('nv-cookie-banner');
        if (banner) banner.hidden = true;
        loadThirdParty(value);
    }

    // Load immediately and keep the banner hidden when consent already exists.
    if (consent) {
        loadThirdParty(consent);
    } else {
        // Show the banner after the DOM is ready.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                var banner = document.getElementById('nv-cookie-banner');
                if (banner) banner.hidden = false;
            });
        } else {
            var banner = document.getElementById('nv-cookie-banner');
            if (banner) banner.hidden = false;
        }
    }

    // Buton event'leri
    document.addEventListener('DOMContentLoaded', function () {
        var btnAccept    = document.getElementById('nv-cookie-accept');
        var btnEssential = document.getElementById('nv-cookie-essential');
        if (btnAccept)    btnAccept.addEventListener('click',    function () { setConsent('all'); });
        if (btnEssential) btnEssential.addEventListener('click', function () { setConsent('essential'); });
    });
}());
</script>
