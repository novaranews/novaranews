@props([
    'namePrefix',
    'noindexChecked' => false,
    'nofollowChecked' => false,
    'errorNoindex' => null,
    'errorNofollow' => null,
])

<div class="rounded-md border border-gray-200 p-3">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_robots_title') }}</p>
    <div class="mt-2 space-y-2">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="{{ $namePrefix }}[robots_noindex]" value="0">
            <input
                type="checkbox"
                name="{{ $namePrefix }}[robots_noindex]"
                value="1"
                @checked($noindexChecked)
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <span>{{ __('site.admin_robots_noindex') }}</span>
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="{{ $namePrefix }}[robots_nofollow]" value="0">
            <input
                type="checkbox"
                name="{{ $namePrefix }}[robots_nofollow]"
                value="1"
                @checked($nofollowChecked)
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <span>{{ __('site.admin_robots_nofollow') }}</span>
        </label>
    </div>
    <p class="mt-2 text-xs text-gray-400">{{ __('site.admin_robots_hint') }}</p>
    @if($errorNoindex)
        <x-input-error :messages="$errorNoindex" class="mt-2" />
    @endif
    @if($errorNofollow)
        <x-input-error :messages="$errorNofollow" class="mt-2" />
    @endif
</div>
