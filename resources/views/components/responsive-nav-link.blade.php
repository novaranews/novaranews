@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-md border-l-4 border-indigo-500 bg-indigo-50 py-2.5 ps-3 pe-4 text-start text-base font-semibold text-indigo-700 transition duration-150 ease-in-out focus:outline-none dark:border-indigo-400 dark:bg-indigo-500/15 dark:text-indigo-200'
            : 'block w-full rounded-md border-l-4 border-transparent py-2.5 ps-3 pe-4 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-800 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
