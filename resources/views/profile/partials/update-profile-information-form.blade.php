<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('site.profile_information_title') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('site.profile_information_desc') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Name --}}
        <div>
            <x-input-label for="name" :value="__('site.common_name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('site.common_email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('site.profile_email_unverified') }}
                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:text-gray-300 dark:hover:text-gray-100 dark:focus:ring-offset-gray-900">
                            {{ __('site.profile_resend_verification') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">{{ __('site.profile_verification_link_sent') }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Public email (author profile) --}}
        <div>
            <x-input-label for="public_email" :value="__('site.profile_public_email_label')" />
            <x-text-input id="public_email" name="public_email" type="email" class="mt-1 block w-full" :value="old('public_email', $user->public_email)" :placeholder="__('site.profile_public_email_placeholder')" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.profile_public_email_help') }}</p>
            <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                <input type="hidden" name="show_public_email" value="0">
                <input type="checkbox" name="show_public_email" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('show_public_email', $user->show_public_email))>
                <span>{{ __('site.profile_visibility_toggle_label') }}</span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('public_email')" />
            <x-input-error class="mt-2" :messages="$errors->get('show_public_email')" />
        </div>

        {{-- Slug (public URL) --}}
        <div>
            <x-input-label for="slug" :value="__('site.profile_slug_label')" />
            <div class="mt-1 flex rounded-md shadow-sm">
                <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">/author/</span>
                <x-text-input id="slug" name="slug" type="text" class="block w-full rounded-l-none" :value="old('slug', $user->slug)" :placeholder="__('site.profile_slug_placeholder')" />
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.profile_slug_help') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('slug')" />
        </div>

        {{-- Title & bio per site language --}}
        <div class="space-y-5 border-t border-gray-200 pt-6 dark:border-gray-700">
            <div>
                <h3 class="text-base font-medium text-gray-900 dark:text-gray-100">{{ __('site.profile_translations_section_title') }}</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('site.profile_translations_intro') }}</p>
            </div>
            @foreach(config('novaranews.locales', ['en']) as $loc)
                @php
                    $tr = $user->profileTranslations->firstWhere('locale', $loc);
                    $defaultLoc = config('novaranews.default_locale', 'en');
                    $titleVal = old("profile_translations.$loc.title", $tr->title ?? ($loc === $defaultLoc ? $user->title : ''));
                    $bioVal = old("profile_translations.$loc.bio", $tr->bio ?? ($loc === $defaultLoc ? $user->bio : ''));
                @endphp
                <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('site.profile_locale_heading', ['locale' => strtoupper($loc)]) }}</p>
                    <div class="mt-3">
                        <x-input-label for="profile_title_{{ $loc }}" :value="__('site.profile_title_label')" />
                        <x-text-input
                            id="profile_title_{{ $loc }}"
                            name="profile_translations[{{ $loc }}][title]"
                            type="text"
                            class="mt-1 block w-full"
                            :value="$titleVal"
                            :placeholder="__('site.profile_title_placeholder')"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('profile_translations.'.$loc.'.title')" />
                    </div>
                    <div class="mt-3">
                        <x-input-label for="profile_bio_{{ $loc }}" :value="__('site.profile_bio_label')" />
                        <textarea
                            id="profile_bio_{{ $loc }}"
                            name="profile_translations[{{ $loc }}][bio]"
                            rows="8"
                            maxlength="8000"
                            class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                            placeholder="{{ __('site.profile_bio_placeholder') }}"
                        >{{ $bioVal }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.profile_bio_html_hint') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('profile_translations.'.$loc.'.bio')" />
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Avatar --}}
        <div>
            <x-input-label for="avatar" :value="__('site.profile_avatar_label')" />
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                     class="h-16 w-16 rounded-full bg-stone-100 object-contain object-center ring-2 ring-gray-200 dark:bg-stone-800">
                <div>
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"
                           class="block text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/40 dark:file:text-indigo-200 dark:hover:file:bg-indigo-900/60" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.profile_avatar_help') }}</p>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        {{-- Social: Twitter / X --}}
        <div>
            <x-input-label for="twitter" :value="__('site.profile_twitter_label')" />
            <div class="mt-1 flex rounded-md shadow-sm">
                <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">@</span>
                <x-text-input id="twitter" name="twitter" type="text" class="block w-full rounded-l-none" :value="old('twitter', $user->twitter)" :placeholder="__('site.profile_twitter_placeholder')" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('twitter')" />
        </div>

        {{-- LinkedIn --}}
        <div>
            <x-input-label for="linkedin" :value="__('site.profile_linkedin_label')" />
            <div class="mt-1 flex rounded-md shadow-sm">
                <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">linkedin.com/in/</span>
                <x-text-input id="linkedin" name="linkedin" type="text" class="block w-full rounded-l-none" :value="old('linkedin', $user->linkedin)" :placeholder="__('site.profile_linkedin_placeholder')" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('linkedin')" />
        </div>

        {{-- Contact --}}
        <div class="rounded-md border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/70">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('site.profile_contact_section_title') }}</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.profile_contact_section_help') }}</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="phone" :value="__('site.profile_phone_label')" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" :placeholder="__('site.profile_phone_placeholder')" />
                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <input type="hidden" name="show_phone" value="0">
                        <input type="checkbox" name="show_phone" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('show_phone', $user->show_phone))>
                        <span>{{ __('site.profile_visibility_toggle_label') }}</span>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    <x-input-error class="mt-2" :messages="$errors->get('show_phone')" />
                </div>
                <div>
                    <x-input-label for="whatsapp" :value="__('site.profile_whatsapp_label')" />
                    <x-text-input id="whatsapp" name="whatsapp" type="text" class="mt-1 block w-full" :value="old('whatsapp', $user->whatsapp)" :placeholder="__('site.profile_whatsapp_placeholder')" />
                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <input type="hidden" name="show_whatsapp" value="0">
                        <input type="checkbox" name="show_whatsapp" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('show_whatsapp', $user->show_whatsapp))>
                        <span>{{ __('site.profile_visibility_toggle_label') }}</span>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('whatsapp')" />
                    <x-input-error class="mt-2" :messages="$errors->get('show_whatsapp')" />
                </div>
                <div>
                    <x-input-label for="telegram" :value="__('site.profile_telegram_label')" />
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">@</span>
                        <x-text-input id="telegram" name="telegram" type="text" class="block w-full rounded-l-none" :value="old('telegram', $user->telegram)" :placeholder="__('site.profile_telegram_placeholder')" />
                    </div>
                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <input type="hidden" name="show_telegram" value="0">
                        <input type="checkbox" name="show_telegram" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('show_telegram', $user->show_telegram))>
                        <span>{{ __('site.profile_visibility_toggle_label') }}</span>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('telegram')" />
                    <x-input-error class="mt-2" :messages="$errors->get('show_telegram')" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="address" :value="__('site.profile_address_label')" />
                    <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $user->address)" :placeholder="__('site.profile_address_placeholder')" />
                    <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <input type="hidden" name="show_address" value="0">
                        <input type="checkbox" name="show_address" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('show_address', $user->show_address))>
                        <span>{{ __('site.profile_visibility_toggle_label') }}</span>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('address')" />
                    <x-input-error class="mt-2" :messages="$errors->get('show_address')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('site.common_save') }}</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p data-nv-profile-flash class="text-sm text-gray-600 dark:text-gray-400">{{ __('site.common_saved') }}</p>
            @endif
        </div>
    </form>
</section>
