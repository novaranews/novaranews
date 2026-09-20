@php
    $locale = app()->getLocale();
    $commentUrl = route('comments.store', ['locale' => $locale, 'article' => $article->id]);
    $recaptchaSiteKey = config('novaranews.recaptcha.site_key');
@endphp

<section class="mx-auto mt-10 max-w-3xl" id="comments" aria-labelledby="comments-heading">
    <div class="border-t border-stone-200 pt-8 dark:border-stone-700">

        {{-- Success message. --}}
        @if(session('comment_status') === 'pending')
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-800/50 dark:bg-green-950/30 dark:text-green-300">
                {{ __('site.comment_pending_notice') }}
            </div>
        @endif

        {{-- Approved comments. --}}
        @if($comments->isNotEmpty())
            <h2 id="comments-heading" class="font-serif text-xl font-bold text-stone-900 dark:text-stone-100">
                {{ __('site.comments_heading', ['count' => $comments->count()]) }}
            </h2>

            <div class="mt-4 space-y-6">
                @foreach($comments as $comment)
                    <div class="rounded-xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-900" id="comment-{{ $comment->id }}">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-novara-100 text-sm font-bold text-novara-800 dark:bg-stone-700 dark:text-stone-200">
                                {{ mb_strtoupper(mb_substr($comment->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-stone-900 dark:text-stone-100">{!! htmlspecialchars($comment->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                                <p class="text-xs text-stone-500 dark:text-stone-400">{{ $comment->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-stone-700 dark:text-stone-300">{!! htmlspecialchars($comment->body, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>

                        {{-- Reply button. --}}
                        <button
                            type="button"
                            class="mt-2 text-xs font-medium text-novara-800 hover:underline dark:text-sky-400"
                            onclick="document.getElementById('reply-form-{{ $comment->id }}').classList.toggle('hidden');document.getElementById('reply-form-{{ $comment->id }}').querySelector('input[name=parent_id]').value='{{ $comment->id }}'">
                            ↩ {{ __('site.comment_reply') }}
                        </button>

                        {{-- Reply form. --}}
                        <div id="reply-form-{{ $comment->id }}" class="hidden mt-3">
                            <form method="POST" action="{{ $commentUrl }}" id="reply-form-inner-{{ $comment->id }}">
                                @csrf
                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                <input type="hidden" name="g-recaptcha-response" class="g-recaptcha-token">
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <input type="text" name="name" placeholder="{{ __('site.comment_form_name') }}" required maxlength="120"
                                           class="w-full rounded border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
                                    <input type="email" name="email" placeholder="{{ __('site.comment_form_email') }}" required
                                           class="w-full rounded border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
                                </div>
                                <textarea name="body" rows="2" placeholder="{{ __('site.comment_form_reply_placeholder') }}" required minlength="3" maxlength="2000"
                                          class="mt-2 w-full rounded border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100"></textarea>
                                @if($recaptchaSiteKey)
                                    <div class="mt-2">
                                        @include('site.partials.recaptcha-disclosure')
                                    </div>
                                @endif
                                <button type="submit"
                                    class="mt-2 rounded bg-novara-800 px-4 py-1.5 text-xs font-semibold text-white hover:bg-novara-900 dark:bg-sky-700 dark:hover:bg-sky-600">
                                    {{ __('site.comment_form_send') }}
                                </button>
                            </form>
                        </div>

                        {{-- Approved replies. --}}
                        @if($comment->replies->isNotEmpty())
                            <div class="mt-4 space-y-3 border-l-2 border-stone-200 pl-4 dark:border-stone-700">
                                @foreach($comment->replies as $reply)
                                    <div class="rounded-lg bg-stone-50 p-3 dark:bg-stone-800/50" id="comment-{{ $reply->id }}">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-stone-200 text-xs font-bold text-stone-700 dark:bg-stone-700 dark:text-stone-300">
                                                {{ mb_strtoupper(mb_substr($reply->name, 0, 1)) }}
                                            </div>
                                            <p class="text-xs font-semibold text-stone-800 dark:text-stone-200">{!! htmlspecialchars($reply->name, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                                            <p class="text-xs text-stone-400">{{ $reply->created_at->diffForHumans() }}</p>
                                        </div>
                                        <p class="mt-2 text-sm leading-relaxed text-stone-700 dark:text-stone-300">{!! htmlspecialchars($reply->body, ENT_COMPAT | ENT_HTML5, 'UTF-8') !!}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Yorum formu --}}
        <div class="mt-8">
            <h2 class="font-serif text-xl font-bold text-stone-900 dark:text-stone-100" id="comment-form-heading">
                {{ __('site.comment_form_heading') }}
            </h2>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ __('site.comment_moderation_notice') }}</p>

            @error('captcha')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ $commentUrl }}" class="mt-4 space-y-3" id="main-comment-form">
                @csrf
                <input type="hidden" name="parent_id" value="">
                <input type="hidden" name="g-recaptcha-response" id="main-g-recaptcha-token">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <input type="text" name="name" id="comment_name" value="{{ old('name') }}"
                               placeholder="{{ __('site.comment_form_name') }}" required maxlength="120"
                               class="w-full rounded border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none focus:ring-1 focus:ring-novara-800 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input type="email" name="email" id="comment_email" value="{{ old('email') }}"
                               placeholder="{{ __('site.comment_form_email') }}" required
                               class="w-full rounded border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none focus:ring-1 focus:ring-novara-800 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <textarea name="body" id="comment_body" rows="4"
                              placeholder="{{ __('site.comment_form_placeholder') }}" required minlength="3" maxlength="2000"
                              class="w-full rounded border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-novara-800 focus:outline-none focus:ring-1 focus:ring-novara-800 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100">{{ old('body') }}</textarea>
                    @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <p class="text-xs text-stone-400 dark:text-stone-500">{{ __('site.comment_email_privacy') }}</p>
                @if($recaptchaSiteKey)
                    @include('site.partials.recaptcha-disclosure')
                @endif
                <button type="submit"
                    class="rounded-lg bg-novara-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-novara-900 dark:bg-sky-700 dark:hover:bg-sky-600">
                    {{ __('site.comment_form_send') }}
                </button>
            </form>
        </div>
    </div>
</section>

@if($recaptchaSiteKey)
@include('site.partials.recaptcha-badge-style')
<script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
<script>
(function () {
    var siteKey = '{{ $recaptchaSiteKey }}';

    function executeRecaptcha(formEl, tokenInput) {
        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            var form = formEl;
            grecaptcha.ready(function () {
                grecaptcha.execute(siteKey, { action: 'comment' }).then(function (token) {
                    tokenInput.value = token;
                    form.submit();
                });
            });
        });
    }

    // Ana yorum formu
    var mainForm = document.getElementById('main-comment-form');
    var mainToken = document.getElementById('main-g-recaptcha-token');
    if (mainForm && mainToken) executeRecaptcha(mainForm, mainToken);

    // Reply forms are dynamic, so observe them with MutationObserver.
    document.querySelectorAll('[id^="reply-form-inner-"]').forEach(function (form) {
        var tokenInput = form.querySelector('.g-recaptcha-token');
        if (tokenInput) executeRecaptcha(form, tokenInput);
    });
})();
</script>
@endif
