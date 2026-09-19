<x-guest-layout>
    <p class="mb-4 text-sm text-gray-600">{{ __('site.auth_two_factor_challenge_desc') }}</p>

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf

        <div>
            <x-input-label for="code" :value="__('site.auth_authentication_code')" />
            <x-text-input id="code" class="mt-1 block w-full font-mono tracking-widest" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-end">
            <x-primary-button>
                {{ __('site.common_continue') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
