<x-app-layout>
    <x-slot name="header">
        <div class="admin-toolbar">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_ai_sources_title') }}</h2>
            <x-admin.btn :href="route('admin.ai-generations.index')">← {{ __('site.admin_ai_generations_title') }}</x-admin.btn>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-6xl">

            @if (session('success'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-green-800">{{ session('success') }}</div>
            @endif

            {{-- Add new source --}}
            <div class="admin-card p-4 sm:p-6">
                <h3 class="mb-4 font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_add_new_source') }}</h3>
                <form action="{{ route('admin.ai-generations.source-store') }}" method="post" class="grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_source_name') }}</label>
                        <input type="text" name="name" required maxlength="120" class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_source_name_placeholder') }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_rss_url') }}</label>
                        <input type="url" name="url" required maxlength="2048" class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('site.admin_source_rss_placeholder') }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.language') }}</label>
                        <select name="locale" required class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            @foreach($locales as $l)
                                <option value="{{ $l }}">{{ strtoupper($l) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.common_category') }}</label>
                        <select name="category_key" class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">{{ __('site.admin_select_option') }}</option>
                            @foreach($categories as $cat)
                                @php $tr = $cat->translate(app()->getLocale()) ?? $cat->translations->first(); @endphp
                                <option value="{{ $cat->key }}">{{ $tr?->name ?? $cat->key }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <x-admin.btn type="submit" variant="primary">{{ __('site.admin_add') }}</x-admin.btn>
                    </div>
                </form>
            </div>

            {{-- Filters --}}
            <div class="admin-card p-4">
                <form method="get" action="{{ route('admin.ai-generations.sources') }}" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[160px]">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_search_name_or_url') }}</label>
                        <input type="text" name="search" value="{{ $search }}"
                               class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                               placeholder="{{ __('site.admin_source_search_placeholder') }}">
                    </div>
                    <div class="min-w-[120px]">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_status_label') }}</label>
                        <select name="is_active" class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">{{ __('site.admin_filter_all') }}</option>
                            <option value="1" @selected($isActive === '1')>{{ __('site.admin_active') }}</option>
                            <option value="0" @selected($isActive === '0')>{{ __('site.admin_passive') }}</option>
                        </select>
                    </div>
                    <div class="min-w-[100px]">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.language') }}</label>
                        <select name="locale" class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">{{ __('site.admin_filter_all') }}</option>
                            @foreach($locales as $l)
                                <option value="{{ $l }}" @selected($locale === $l)>{{ strtoupper($l) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="min-w-[130px]">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.common_category') }}</label>
                        <select name="category_key" class="w-full rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">{{ __('site.admin_filter_all') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat['key'] }}" @selected($categoryKey === $cat['key'])>{{ $cat['key'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-admin.btn type="submit" variant="primary">{{ __('site.common_filter') }}</x-admin.btn>
                        <x-admin.btn :href="route('admin.ai-generations.sources')">{{ __('site.admin_clear') }}</x-admin.btn>
                    </div>
                </form>
            </div>

            {{-- Action bar --}}
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3">
                    {{-- Bulk delete form --}}
                    <form id="form-bulk-delete" action="{{ route('admin.ai-generations.source-bulk-destroy') }}" method="post"
                          onsubmit="return confirmBulkDelete(event)">
                        @csrf @method('DELETE')
                        <div id="bulk-ids-container"></div>
                        <x-admin.btn id="btn-bulk-delete" type="submit" variant="danger" class="hidden">
                            {{ __('site.admin_delete_selected') }} (<span id="selected-count">0</span>)
                        </x-admin.btn>
                    </form>
                    <span id="check-summary" class="text-sm text-gray-500"></span>
                </div>
                <div class="flex items-center gap-2">
                    <x-admin.btn id="btn-select-errors" type="button" onclick="selectErrors()" variant="soft-danger" class="hidden">
                        {{ __('site.admin_select_errors') }}
                    </x-admin.btn>
                    <x-admin.btn id="btn-check-all" type="button" onclick="checkAllSources()" variant="info"
                            class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                        {{ __('site.admin_check_all') }}
                    </x-admin.btn>
                </div>
            </div>

            {{-- Source list --}}
            <div class="admin-table-wrap">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 w-8">
                                <input type="checkbox" id="chk-all" onchange="toggleAll(this)"
                                       class="rounded border-gray-300 text-gray-800 shadow-sm">
                            </th>
                            <th class="px-4 py-3">{{ __('site.admin_source') }}</th>
                            <th class="px-4 py-3">{{ __('site.language') }}</th>
                            <th class="px-4 py-3">{{ __('site.common_category') }}</th>
                            <th class="px-4 py-3">{{ __('site.admin_last_fetch') }}</th>
                            <th class="px-4 py-3">{{ __('site.admin_status_label') }}</th>
                            <th class="px-4 py-3 w-10 text-center">{{ __('site.admin_rss_url') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="sources-tbody">
                        @forelse($sources as $source)
                            <tr class="hover:bg-gray-50" data-source-id="{{ $source->id }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="row-checkbox rounded border-gray-300 text-gray-800 shadow-sm"
                                           value="{{ $source->id }}" onchange="updateBulkBar()">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $source->name }}</div>
                                    <div class="truncate max-w-xs text-xs text-gray-400" title="{{ $source->url }}">{{ $source->url }}</div>
                                </td>
                                <td class="px-4 py-3 uppercase text-gray-600">{{ $source->locale }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $source->category_key ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $source->last_fetched_at?->format('d.m.Y H:i') ?? __('site.admin_not_fetched_yet') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded px-2 py-1 text-xs font-medium {{ $source->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $source->is_active ? __('site.admin_active') : __('site.admin_passive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="check-indicator text-base" data-id="{{ $source->id }}" title=""></span>
                                </td>
                                <td class="px-4 py-3 flex gap-2">
                                    <form action="{{ route('admin.ai-generations.source-toggle', $source) }}" method="post">
                                        @csrf @method('PATCH')
                                        <x-admin.btn type="submit" class="px-2 py-1 text-xs">
                                            {{ $source->is_active ? __('site.admin_make_passive') : __('site.admin_make_active') }}
                                        </x-admin.btn>
                                    </form>
                                    <form action="{{ route('admin.ai-generations.source-destroy', $source) }}" method="post" data-confirm="{{ __('site.admin_confirm_delete_source') }}" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf @method('DELETE')
                                        <x-admin.btn type="submit" variant="soft-danger" class="px-2 py-1 text-xs">{{ __('site.common_delete') }}</x-admin.btn>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">{{ __('site.admin_source_not_found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div id="ai-source-i18n" hidden
         data-confirm-bulk-delete="{{ __('site.admin_confirm_bulk_delete_sources') }}"
         data-checking="{{ __('site.admin_checking') }}"
         data-server-error="{{ __('site.admin_server_error') }}"
         data-valid="{{ __('site.admin_valid') }}"
         data-invalid="{{ __('site.admin_invalid') }}"
         data-select-errors="{{ __('site.admin_select_errors') }}"
         data-error-label="{{ __('site.admin_error') }}"
         data-check-all="{{ __('site.admin_check_all') }}"></div>
    <script>
    // Track which IDs are errors after check
    const errorIds = new Set();

    function updateBulkBar() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const count = checked.length;
        const btn = document.getElementById('btn-bulk-delete');
        const counter = document.getElementById('selected-count');
        const container = document.getElementById('bulk-ids-container');

        counter.textContent = count;
        btn.classList.toggle('hidden', count === 0);

        // Rebuild hidden inputs
        container.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });

        // Sync header checkbox state
        const all = document.querySelectorAll('.row-checkbox');
        const chkAll = document.getElementById('chk-all');
        chkAll.indeterminate = count > 0 && count < all.length;
        chkAll.checked = count === all.length && all.length > 0;
    }

    function toggleAll(chk) {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = chk.checked);
        updateBulkBar();
    }

    function selectErrors() {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = errorIds.has(parseInt(cb.value));
        });
        updateBulkBar();
    }

    function confirmBulkDelete(e) {
        const i18n = document.getElementById('ai-source-i18n');
        const confirmBulk = i18n ? i18n.getAttribute('data-confirm-bulk-delete') : '';
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        if (!window.confirm(`${count} ${confirmBulk}`)) {
            e.preventDefault();
            return false;
        }
        return true;
    }

    async function checkAllSources() {
        const i18n = document.getElementById('ai-source-i18n');
        const msgChecking = i18n ? i18n.getAttribute('data-checking') : '';
        const msgServerError = i18n ? i18n.getAttribute('data-server-error') : '';
        const msgValid = i18n ? i18n.getAttribute('data-valid') : '';
        const msgInvalid = i18n ? i18n.getAttribute('data-invalid') : '';
        const msgSelectErrors = i18n ? i18n.getAttribute('data-select-errors') : '';
        const msgErrorLabel = i18n ? i18n.getAttribute('data-error-label') : '';
        const msgCheckAll = i18n ? i18n.getAttribute('data-check-all') : '';
        const btn = document.getElementById('btn-check-all');
        const summary = document.getElementById('check-summary');
        const btnSelectErrors = document.getElementById('btn-select-errors');

        btn.disabled = true;
        btn.textContent = msgChecking;
        summary.textContent = '';
        btnSelectErrors.classList.add('hidden');
        errorIds.clear();

        document.querySelectorAll('.check-indicator').forEach(el => {
            el.textContent = '🔄';
            el.title = msgChecking;
        });

        try {
            const response = await fetch("{{ route('admin.ai-generations.source-check-all') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) throw new Error(msgServerError + ': ' + response.status);

            const results = await response.json();
            let ok = 0, error = 0;

            results.forEach(item => {
                const indicator = document.querySelector(`.check-indicator[data-id="${item.id}"]`);
                if (!indicator) return;

                if (item.status === 'ok') {
                    indicator.textContent = '✅';
                    indicator.title = item.message + (item.http_code ? ' (' + item.http_code + ')' : '');
                    ok++;
                } else {
                    indicator.textContent = '❌';
                    indicator.title = item.message + (item.http_code ? ' (' + item.http_code + ')' : '');
                    errorIds.add(item.id);
                    error++;
                }
            });

            summary.textContent = `✅ ${ok} ${msgValid}  ❌ ${error} ${msgInvalid}`;

            if (error > 0) {
                btnSelectErrors.textContent = `${msgSelectErrors} (${error})`;
                btnSelectErrors.classList.remove('hidden');
            }

        } catch (e) {
            summary.textContent = msgErrorLabel + ': ' + e.message;
        } finally {
            btn.disabled = false;
            btn.textContent = msgCheckAll;
        }
    }
    </script>
</x-app-layout>
