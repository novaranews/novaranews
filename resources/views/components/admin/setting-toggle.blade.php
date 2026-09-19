@props([
    'label',
    'name',
    'checked' => false,
    'badgeType' => null,
    'hint' => null,
])

<div>
    <div class="flex items-center justify-between">
        <div>
            <label class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</label>
            @if($badgeType)
                <x-admin.config-source-badge class="ml-2" :type="$badgeType" />
            @endif
        </div>
        <label class="relative inline-flex cursor-pointer items-center">
            <input type="hidden" name="{{ $name }}" value="0">
            <input type="checkbox" name="{{ $name }}" value="1" class="sr-only peer" @checked($checked)>
            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-gray-700 dark:peer-checked:bg-indigo-500"></div>
        </label>
    </div>
    @if($hint)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
