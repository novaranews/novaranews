@extends('site.layout')

@section('title', $seo->title)
@section('canonical', $seo->canonical)

@push('hreflang')
    @include('site.partials.hreflang', ['localeSwitchUrls' => $localeSwitchUrls])
@endpush

@push('meta')
    <x-site.seo-meta :seo="$seo" />
@endpush

@section('content')
@php $recaptchaSiteKey = config('novaranews.recaptcha.site_key'); @endphp
<div class="nv-container max-w-3xl py-10">
    <h1 class="font-serif text-3xl font-bold text-stone-900 dark:text-stone-100">{{ $pageHeading }}</h1>

    @if(session('contact_status') === 'sent')
        <p class="mt-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-900">{{ __('site.contact_sent') }}</p>
    @endif

    @if(!empty($pageContent))
        <div class="nv-static-prose mt-4 [&_p]:mb-3">
            {!! $pageContent !!}
        </div>
    @endif

    @php $contactEmail = \App\Models\Setting::site('contact_mail_to', config('novaranews.contact_mail_to')); @endphp
    @if($contactEmail)
        <p class="mt-4 text-stone-600">
            {{ __('site.contact') }}:
            <a href="mailto:{{ $contactEmail }}" class="text-novara-800 underline">{{ $contactEmail }}</a>
        </p>
    @endif

    <form method="post" action="{{ page_url('contact') }}" class="mt-8 space-y-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-700 dark:bg-stone-900" id="contact-form" data-captcha-failed="{{ e(__('site.contact_captcha_failed')) }}">
        @csrf
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="contact_name" class="block text-sm font-semibold text-stone-800 dark:text-stone-200">{{ __('site.contact_form_name') }}</label>
                <input id="contact_name" name="name" type="text" value="{{ old('name') }}" required maxlength="120"
                       class="mt-1 w-full rounded border border-stone-300 bg-white px-3 py-2 text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none focus:ring-1 focus:ring-novara-800 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="contact_email" class="block text-sm font-semibold text-stone-800 dark:text-stone-200">{{ __('site.contact_form_email') }}</label>
                <input id="contact_email" name="email" type="email" value="{{ old('email') }}" required
                       class="mt-1 w-full rounded border border-stone-300 bg-white px-3 py-2 text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none focus:ring-1 focus:ring-novara-800 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label for="contact_subject" class="block text-sm font-semibold text-stone-800 dark:text-stone-200">{{ __('site.contact_form_subject') }}</label>
            <input id="contact_subject" name="subject" type="text" value="{{ old('subject') }}" maxlength="255"
                   class="mt-1 w-full rounded border border-stone-300 bg-white px-3 py-2 text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none focus:ring-1 focus:ring-novara-800 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
            @error('subject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="contact_message" class="block text-sm font-semibold text-stone-800 dark:text-stone-200">{{ __('site.contact_form_message') }}</label>
            <textarea id="contact_message" name="message" rows="6" required maxlength="5000"
                      class="mt-1 w-full rounded border border-stone-300 bg-white px-3 py-2 text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none focus:ring-1 focus:ring-novara-800 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">{{ old('message') }}</textarea>
            @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        @error('captcha')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <div>
            <x-site.btn type="submit" variant="primary" id="contact-submit">{{ __('site.contact_form_send') }}</x-site.btn>
        </div>
        @if($recaptchaSiteKey)
            @include('site.partials.recaptcha-disclosure')
        @endif
    </form>

    {{-- Yorum formlarıyla aynı: site key yoksa reCAPTCHA yok; aksi halde submit hep preventDefault olur ve form hiç gitmez. --}}
    @if($recaptchaSiteKey)
    @include('site.partials.recaptcha-badge-style')
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
    <script>
        (function () {
            var form = document.getElementById('contact-form');
            if (!form) return;
            var siteKey = '{{ $recaptchaSiteKey }}';
            var captchaFailedMsg = form.getAttribute('data-captcha-failed') || '';
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var el = this;
                if (typeof grecaptcha === 'undefined') {
                    window.alert(captchaFailedMsg);
                    return;
                }
                grecaptcha.ready(function () {
                    grecaptcha.execute(siteKey, { action: 'contact' }).then(function (token) {
                        var input = document.getElementById('g-recaptcha-response');
                        if (input) input.value = token;
                        el.submit();
                    }).catch(function () {
                        window.alert(captchaFailedMsg);
                    });
                });
            });
        })();
    </script>
    @endif
</div>
@endsection
