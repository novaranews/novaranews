<x-app-layout>
    <x-slot name="header">
        <div class="admin-toolbar">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_categories_title') }}</h2>
            <x-admin.btn :href="route('admin.categories.create')" variant="primary" class="w-fit">{{ __('site.admin_new_category') }}</x-admin.btn>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-5xl">
            @if (session('success'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-2 text-red-800">{{ session('error') }}</div>
            @endif
            <form method="get" class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex-1 min-w-[200px]">
                    <label for="q" class="block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_search_key_name_slug') }}</label>
                    <input type="text" id="q" name="q" value="{{ $q ?? '' }}" placeholder="{{ __('site.admin_search_placeholder') }}" class="mt-1 w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                </div>
                <x-admin.btn type="submit" variant="primary">{{ __('site.common_filter') }}</x-admin.btn>
                <x-admin.btn :href="route('admin.categories.index')">{{ __('site.common_reset') }}</x-admin.btn>
            </form>
            <div class="admin-table-wrap">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_key_label') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_sort_label') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_name_en_label') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($categories as $cat)
                            @php $en = $cat->translate('en'); @endphp
                            <tr>
                                <td class="px-4 py-3 font-mono text-sm text-gray-900 dark:text-gray-100">{{ $cat->key }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $cat->sort_order }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $en?->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <x-admin.btn :href="route('admin.categories.edit', $cat)" class="px-2 py-1 text-xs">{{ __('site.common_edit') }}</x-admin.btn>
                                    <form action="{{ route('admin.categories.destroy', $cat) }}" method="post" class="inline ms-2 js-confirm-delete" data-confirm="{{ e(__('site.admin_delete_this_category')) }}">
                                        @csrf
                                        @method('delete')
                                        <x-admin.btn type="submit" variant="soft-danger" class="px-2 py-1 text-xs">{{ __('site.common_delete') }}</x-admin.btn>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @once
        @push('scripts')
            <script>
                document.querySelectorAll('form.js-confirm-delete').forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        if (!confirm(form.getAttribute('data-confirm'))) {
                            e.preventDefault();
                        }
                    });
                });
            </script>
        @endpush
    @endonce
</x-app-layout>
