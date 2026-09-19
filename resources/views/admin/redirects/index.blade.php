<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_redirects_title') }}</h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">
            @if (session('success'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="admin-card p-4 sm:p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('site.admin_redirects_create_title') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_redirects_create_desc') }}</p>
                <form method="POST" action="{{ route('admin.redirects.store') }}" class="mt-4 grid gap-3 sm:grid-cols-5">
                    @csrf
                    <div>
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_redirects_locale_optional') }}</label>
                        <input type="text" name="locale" maxlength="8" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_redirects_locale_placeholder') }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_redirects_from_path') }}</label>
                        <input type="text" name="from_path" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_redirects_from_path_placeholder') }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_redirects_to_url') }}</label>
                        <input type="url" name="to_url" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_redirects_to_url_placeholder') }}">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_redirects_status') }}</label>
                        <select name="status_code" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="301">301</option>
                            <option value="302">302</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600">
                            {{ __('site.admin_redirects_active') }}
                        </label>
                    </div>
                    <div class="sm:col-span-3 flex items-end">
                        <x-admin.btn type="submit" variant="info">{{ __('site.admin_redirects_save') }}</x-admin.btn>
                    </div>
                </form>
            </div>

            <div class="admin-card p-4 sm:p-6">
                <form method="GET" class="mb-4 flex gap-2">
                    <input type="text" name="q" value="{{ $q }}" class="block w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_redirects_search_placeholder') }}">
                    <x-admin.btn type="submit">{{ __('site.common_filter') }}</x-admin.btn>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ __('site.admin_redirects_table_locale') }}</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ __('site.admin_redirects_table_from') }}</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ __('site.admin_redirects_table_to') }}</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ __('site.admin_redirects_table_code') }}</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ __('site.admin_redirects_table_active') }}</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($items as $row)
                                <tr>
                                    <td class="px-3 py-2">{{ $row->locale ?: '-' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $row->from_path }}</td>
                                    <td class="px-3 py-2 break-all">{{ $row->to_url }}</td>
                                    <td class="px-3 py-2">{{ $row->status_code }}</td>
                                    <td class="px-3 py-2">{{ $row->is_active ? __('site.admin_redirects_yes') : __('site.admin_redirects_no') }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <form method="POST" action="{{ route('admin.redirects.destroy', $row) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.btn type="submit" variant="soft-danger" class="px-2 py-1 text-xs">{{ __('site.common_delete') }}</x-admin.btn>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('site.admin_redirects_empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
