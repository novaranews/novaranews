<x-app-layout>
    <x-slot name="header">
        <div class="admin-toolbar">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">
                {{ __('site.admin_contact_messages_title') }}
                @if($unreadCount > 0)
                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-500/20 dark:text-red-300">{{ __('site.admin_contact_unread_count', ['count' => $unreadCount]) }}</span>
                @endif
            </h2>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">

            @if(session('status'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('status') }}</div>
            @endif

            {{-- Filter tabs --}}
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.contact-messages.index') }}"
                   class="rounded px-3 py-1.5 font-medium {{ $filter === 'all' ? 'bg-gray-800 text-white dark:bg-gray-700' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    {{ __('site.admin_filter_all') }}
                </a>
                <a href="{{ route('admin.contact-messages.index', ['filter' => 'unread']) }}"
                   class="rounded px-3 py-1.5 font-medium {{ $filter === 'unread' ? 'bg-gray-800 text-white dark:bg-gray-700' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    {{ __('site.admin_filter_unread') }}
                </a>
                <a href="{{ route('admin.contact-messages.index', ['filter' => 'read']) }}"
                   class="rounded px-3 py-1.5 font-medium {{ $filter === 'read' ? 'bg-gray-800 text-white dark:bg-gray-700' : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    {{ __('site.admin_filter_read') }}
                </a>
            </div>

            @if($messages->isEmpty())
                <div class="rounded border border-gray-200 bg-white p-8 text-center text-gray-500">
                    {{ __('site.admin_contact_empty') }}
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 w-8"></th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_contact_sender') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_contact_subject_message') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.language') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('site.admin_contact_date') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($messages as $msg)
                                <tr class="{{ $msg->isRead() ? 'bg-white' : 'bg-blue-50' }}">
                                    <td class="px-4 py-3">
                                        @if(!$msg->isRead())
                                            <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $msg->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $msg->email }}</div>
                                    </td>
                                    <td class="px-4 py-3 max-w-xs">
                                        @if($msg->subject)
                                            <div class="font-medium text-gray-800">{{ $msg->subject }}</div>
                                        @endif
                                        <div class="text-gray-500 truncate">{{ Str::limit($msg->message, 80) }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold uppercase text-gray-600">{{ $msg->locale }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $msg->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-admin.btn :href="route('admin.contact-messages.show', $msg)" class="px-2 py-1 text-xs">
                                            {{ __('site.admin_contact_view') }}
                                        </x-admin.btn>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>{{ $messages->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
