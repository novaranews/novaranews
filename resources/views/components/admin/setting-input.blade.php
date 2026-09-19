@props([
    'label',
    'name',
    'value' => null,
    'type' => 'text',
    'badgeType' => null,
    'maxlength' => null,
    'min' => null,
    'max' => null,
    'rows' => 3,
    'placeholder' => null,
    'hint' => null,
    'inputClass' => 'mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400 dark:focus:border-indigo-400 dark:focus:ring-indigo-400',
])

<div>
    <label class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</label>
    @if($badgeType)
        <x-admin.config-source-badge class="mt-1" :type="$badgeType" />
    @endif
    @if($type === 'textarea')
        <textarea
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if($maxlength !== null) maxlength="{{ $maxlength }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="{{ $inputClass }}"
        >{{ $value }}</textarea>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ $value }}"
            @if($maxlength !== null) maxlength="{{ $maxlength }}" @endif
            @if($min !== null) min="{{ $min }}" @endif
            @if($max !== null) max="{{ $max }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="{{ $inputClass }}"
        >
    @endif
    @if($hint)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
</div>
