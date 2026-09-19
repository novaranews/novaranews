@if($user->is_admin)
    @php
        $hasTwoFactorRoutes =
            \Illuminate\Support\Facades\Route::has('profile.two-factor.start') &&
            \Illuminate\Support\Facades\Route::has('profile.two-factor.confirm') &&
            \Illuminate\Support\Facades\Route::has('profile.two-factor.cancel') &&
            \Illuminate\Support\Facades\Route::has('profile.two-factor.destroy');
    @endphp
    <div class="space-y-4">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('site.profile_2fa_title') }}</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('site.profile_2fa_desc') }}</p>
        </header>

        @if (session('status') === 'two-factor-enabled')
            <p class="text-sm font-medium text-green-600">{{ __('site.profile_2fa_enabled') }}</p>
        @endif
        @if (session('status') === 'two-factor-disabled')
            <p class="text-sm font-medium text-green-600">{{ __('site.profile_2fa_disabled') }}</p>
        @endif

        @if($user->hasTwoFactorEnabled())
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ __('site.profile_status_enabled') }}</p>
            @if($hasTwoFactorRoutes)
                <form method="post" action="{{ route('profile.two-factor.destroy') }}" class="space-y-3">
                    @csrf
                    @method('delete')
                    <div>
                        <x-input-label for="two_factor_disable_password" :value="__('site.profile_current_password')" />
                        <x-text-input id="two_factor_disable_password" name="password" type="password" class="mt-1 block w-full" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <x-danger-button type="submit">{{ __('site.profile_disable_2fa') }}</x-danger-button>
                </form>
            @else
                <p class="text-sm text-amber-700">
                    {{ __('site.profile_2fa_temporarily_unavailable') }}
                </p>
            @endif
        @else
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ __('site.profile_status_not_enabled') }}</p>

            @if($twoFactorOtpauth ?? null)
                @if($hasTwoFactorRoutes)
                    <div class="rounded border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/70">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ __('site.profile_2fa_scan_qr') }}</p>
                        <div class="mt-3 flex justify-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&amp;data={{ urlencode($twoFactorOtpauth) }}" width="180" height="180" alt="" loading="lazy">
                        </div>
                        <form method="post" action="{{ route('profile.two-factor.confirm') }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="two_factor_code" :value="__('site.profile_2fa_code_label')" />
                                <x-text-input id="two_factor_code" name="code" type="text" class="mt-1 block w-full font-mono" inputmode="numeric" maxlength="6" required autocomplete="one-time-code" />
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-primary-button type="submit">{{ __('site.profile_2fa_confirm_enable') }}</x-primary-button>
                            </div>
                        </form>
                        <form method="post" action="{{ route('profile.two-factor.cancel') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">{{ __('site.profile_2fa_cancel_setup') }}</button>
                        </form>
                    </div>
                @else
                    <p class="text-sm text-amber-700">
                        {{ __('site.profile_2fa_temporarily_unavailable') }}
                    </p>
                @endif
            @else
                @if($hasTwoFactorRoutes)
                    <form method="post" action="{{ route('profile.two-factor.start') }}">
                        @csrf
                        <x-secondary-button type="submit">{{ __('site.profile_2fa_start_setup') }}</x-secondary-button>
                    </form>
                @else
                    <p class="text-sm text-amber-700">
                        {{ __('site.profile_2fa_temporarily_unavailable') }}
                    </p>
                @endif
            @endif
        @endif
    </div>
@endif
