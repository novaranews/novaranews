@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center rounded-md border-l-4 border-indigo-500 bg-indigo-50 px-3 py-2.5 text-base font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none dark:border-indigo-400 dark:bg-indigo-500/15 dark:text-indigo-200 dark:hover:bg-indigo-500/20'
            : 'flex items-center rounded-md border-l-4 border-transparent px-3 py-2.5 text-base font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
