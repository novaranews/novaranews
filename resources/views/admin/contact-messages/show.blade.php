<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <x-admin.btn :href="route('admin.contact-messages.index')">← {{ __('site.admin_contact_back') }}</x-admin.btn>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">{{ __('site.admin_contact_detail') }}</h2>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-3xl">

            @if(session('status'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm divide-y divide-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:divide-gray-800">
                <div class="grid gap-4 px-6 py-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('site.admin_contact_sender') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $message->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('site.admin_contact_email') }}</dt>
                        <dd class="mt-1">
                            <a href="mailto:{{ $message->email }}" class="text-indigo-600 hover:underline dark:text-indigo-300">{{ $message->email }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('site.admin_contact_date') }} / {{ __('site.language') }}</dt>
                        <dd class="mt-1 text-gray-700 dark:text-gray-300">{{ $message->created_at->format('d M Y H:i') }} &middot; <span class="font-semibold uppercase">{{ $message->locale }}</span></dd>
                    </div>
                </div>

                @if($message->subject)
                    <div class="px-6 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('site.admin_contact_subject') }}</dt>
                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $message->subject }}</dd>
                    </div>
                @endif

                <div class="px-6 py-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('site.admin_contact_message') }}</dt>
                    <dd class="mt-2 whitespace-pre-wrap leading-relaxed text-gray-800 dark:text-gray-200">{{ $message->message }}</dd>
                </div>

                @if($message->ip)
                    <div class="bg-gray-50 px-6 py-3 dark:bg-gray-800">
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_ip_label') }}: {{ $message->ip }}</span>
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <x-admin.btn
                    href="mailto:{{ $message->email }}?subject=Re: {{ $message->subject ?? __('site.admin_contact_reply_subject_default', ['site' => config('app.name')]) }}"
                    variant="info"
                    class="font-semibold">
                    ✉ {{ __('site.admin_contact_reply') }}
                </x-admin.btn>

                @if($message->isRead())
                    <form method="post" action="{{ route('admin.contact-messages.unread', $message) }}">
                        @csrf @method('patch')
                        <x-admin.btn type="submit">{{ __('site.admin_contact_mark_unread') }}</x-admin.btn>
                    </form>
                @else
                    <form method="post" action="{{ route('admin.contact-messages.read', $message) }}">
                        @csrf @method('patch')
                        <x-admin.btn type="submit">{{ __('site.admin_contact_mark_read') }}</x-admin.btn>
                    </form>
                @endif

                <form method="post" action="{{ route('admin.contact-messages.destroy', $message) }}"
                      data-confirm="{{ __('site.admin_contact_delete_confirm') }}"
                      onsubmit="return confirm(this.dataset.confirm)">
                    @csrf @method('delete')
                    <x-admin.btn type="submit" variant="danger">{{ __('site.common_delete') }}</x-admin.btn>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
