<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_governance_audits_title') }}</h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">
            <div class="admin-card p-4">
                <div class="flex flex-wrap items-center gap-2">
                <form method="GET" class="flex gap-2">
                    <input type="text" name="event" value="{{ $event }}" class="w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_governance_audits_event_placeholder') }}">
                    <x-admin.btn type="submit">{{ __('site.common_filter') }}</x-admin.btn>
                </form>
                    <form method="POST" action="{{ route('admin.governance.audits.clear') }}" data-confirm-summary="{{ __('site.admin_governance_audits_clear_confirm') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="event" value="{{ $event }}">
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
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_event') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_user') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_target') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.admin_governance_col_created') }}</th>
                                <th class="px-3 py-2 text-left text-gray-700 dark:text-gray-300">{{ __('site.common_action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-3 py-2">{{ $row->id }}</td>
                                    <td class="px-3 py-2">{{ $row->event_type }}</td>
                                    <td class="px-3 py-2">{{ $row->user_id ?: '-' }}</td>
                                    <td class="px-3 py-2">{{ $row->auditable_type ? class_basename($row->auditable_type).'#'.$row->auditable_id : '-' }}</td>
                                    <td class="px-3 py-2">{{ $row->created_at }}</td>
                                    <td class="px-3 py-2">
                                        <form method="POST" action="{{ route('admin.governance.audits.destroy', $row) }}" data-confirm-summary="{{ __('site.admin_governance_audit_delete_confirm') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn-soft-danger px-2 py-1 text-xs">{{ __('site.common_delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">{{ __('site.admin_governance_audits_empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $rows->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
