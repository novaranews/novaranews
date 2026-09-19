@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    data-nv-modal
    data-nv-modal-name="{{ $name }}"
    data-nv-modal-initial="{{ $show ? '1' : '0' }}"
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6 sm:px-0 {{ $show ? '' : 'hidden' }}"
>
    <div
        data-nv-modal-backdrop
        class="absolute inset-0 z-0 bg-gray-500 opacity-75"
        aria-hidden="true"
    ></div>

    <div class="relative z-10 mb-6 w-full {{ $maxWidth }} sm:mx-auto">
        <div class="overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-900">
            {{ $slot }}
        </div>
    </div>
</div>
