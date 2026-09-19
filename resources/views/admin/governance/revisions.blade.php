<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_governance_revisions_title') }}</h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">
            <div class="admin-card p-4">
                <div class="flex flex-wrap items-center gap-2">
                <form method="GET" class="flex gap-2">
                    <input type="number" min="1" name="article_id" value="{{ $articleId ?: '' }}" class="w-48 rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_governance_revisions_article_id_placeholder') }}">
                    <x-admin.btn type="submit">{{ __('site.common_filter') }}</x-admin.btn>
                </form>
                    <form method="POST" action="{{ route('admin.governance.revisions.clear') }}" data-confirm-summary="{{ __('site.admin_governance_revisions_clear_confirm') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="article_id" value="{{ $articleId ?: '' }}">
                        <button type="submit" class="admin-btn-soft-danger">{{ __('site.admin_governance_bulk_delete_filtered') }}</button>
                    </form>
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ __('site.admin_governance_only_logs_deleted') }}</p>
            </div>
            <div class="admin-card p-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_id') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_article') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_locale') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_reason') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_created') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.common_action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-3 py-2">{{ $row->id }}</td>
                                    <td class="px-3 py-2">#{{ $row->article_id }}</td>
                                    <td class="px-3 py-2">{{ $row->locale }}</td>
                                    <td class="px-3 py-2">{{ $row->reason }}</td>
                                    <td class="px-3 py-2">{{ $row->created_at }}</td>
                                    <td class="px-3 py-2">
                                        <form method="POST" action="{{ route('admin.governance.revisions.destroy', $row) }}" data-confirm-summary="{{ __('site.admin_governance_revision_delete_confirm') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn-soft-danger px-2 py-1 text-xs">{{ __('site.common_delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('site.admin_governance_revisions_empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
