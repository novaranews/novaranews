<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">{{ __('site.admin_static_pages_title') }}</h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-5xl">
            @if(session('success'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-900">{{ session('success') }}</div>
            @endif

            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('site.admin_static_pages_key') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('site.admin_static_pages_status') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('site.admin_static_pages_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                        @foreach($pages as $page)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $page->key }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($page->is_active)
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            {{ __('site.admin_static_page_enabled') }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 dark:border-rose-800 dark:bg-rose-500/10 dark:text-rose-300">
                                            {{ __('site.admin_static_page_disabled') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <x-admin.btn :href="route('admin.static-pages.edit', $page)">{{ __('site.common_edit') }}</x-admin.btn>
                                    @if($page->is_active)
                                        <form method="POST" action="{{ route('admin.static-pages.destroy', $page) }}" class="ml-2 inline">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.btn type="submit" variant="soft-danger" onclick="return confirm(this.dataset.confirm)" data-confirm="{{ __('site.admin_static_page_delete_confirm') }}">
                                                {{ __('site.common_delete') }}
                                            </x-admin.btn>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.static-pages.restore', $page) }}" class="ml-2 inline">
                                            @csrf
                                            @method('PATCH')
                                            <x-admin.btn type="submit" variant="soft-warning">
                                                {{ __('site.admin_static_page_restore') }}
                                            </x-admin.btn>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_static_page_disabled_hint') }}</p>
        </div>
    </div>
</x-app-layout>
