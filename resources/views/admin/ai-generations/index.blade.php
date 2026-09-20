<x-app-layout>
    <x-slot name="header">
        <div class="admin-toolbar">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_ai_generations_title') }}</h2>
            <x-admin.btn :href="route('admin.ai-generations.sources')">{{ __('site.admin_ai_sources_title') }}</x-admin.btn>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-7xl">

            @if (session('success'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">{{ $errors->first() }}</div>
            @endif

            {{-- ═══ BOT CONTROL PANEL ═══ --}}
            <div class="admin-card border-indigo-200 dark:border-indigo-800">
                <div class="border-b border-indigo-100 bg-indigo-50 px-6 py-4 dark:border-indigo-800 dark:bg-indigo-500/10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-indigo-900 dark:text-indigo-300">{{ __('site.admin_bot_control_panel') }}</h3>
                            <p class="mt-0.5 text-xs text-indigo-600 dark:text-indigo-300/80">
                                {{ __('site.admin_model') }}: <strong>{{ $currentModel }}</strong> &nbsp;·&nbsp;
                                {{ __('site.admin_status_label') }}: <strong class="{{ $botEnabled ? 'text-green-700' : 'text-red-600' }}">{{ $botEnabled ? __('site.admin_enabled') : __('site.admin_disabled') }}</strong> &nbsp;·&nbsp;
                                {{ __('site.admin_auto_publish_label') }}: <strong>{{ $autoPublish ? __('site.admin_on') : __('site.admin_off_editor_review') }}</strong>
                            </p>
                        </div>
                        <x-admin.btn :href="route('admin.settings.index')" class="border-indigo-300 text-indigo-700 hover:bg-indigo-50">
                            {{ __('site.admin_nav_settings') }}
                        </x-admin.btn>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-4 divide-x divide-gray-100 border-b border-gray-100 dark:divide-gray-800 dark:border-gray-800">
                    @foreach([
                        ['label' => __('site.admin_dashboard_ai_pending'),    'count' => $stats['pending'],    'color' => 'text-yellow-600'],
                        ['label' => __('site.admin_dashboard_ai_processing'), 'count' => $stats['processing'], 'color' => 'text-blue-600'],
                        ['label' => __('site.admin_ai_done'),                 'count' => $stats['done'],       'color' => 'text-green-600'],
                        ['label' => __('site.admin_dashboard_ai_failed'),     'count' => $stats['failed'],     'color' => 'text-red-600'],
                    ] as $stat)
                        <div class="px-6 py-4 text-center">
                            <div class="text-2xl font-bold {{ $stat['color'] }}">{{ $stat['count'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Run Now --}}
                <form method="POST" action="{{ route('admin.ai-generations.run') }}" class="flex flex-wrap items-end gap-4 px-6 py-5">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.language') }}</label>
                        <select name="locale" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                            <option value="">{{ __('site.admin_all_languages') }}</option>
                            <option value="tr">🇹🇷 {{ __('site.admin_lang_tr') }}</option>
                            <option value="en">🇬🇧 {{ __('site.admin_lang_en') }}</option>
                            <option value="de">🇩🇪 {{ __('site.admin_lang_de') }}</option>
                            <option value="fr">🇫🇷 {{ __('site.admin_lang_fr') }}</option>
                            <option value="es">🇪🇸 {{ __('site.admin_lang_es') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.common_category') }}</label>
                        <select name="category_key" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                            <option value="">{{ __('site.admin_all_categories') }}</option>
                            @foreach($categories as $cat)
                                @php $tr = $cat->translate(app()->getLocale()) ?? $cat->translations->first(); @endphp
                                <option value="{{ $cat->key }}">{{ $tr?->name ?? $cat->key }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_news_count') }}</label>
                        <input type="number" name="limit" value="{{ $articlesPerRun }}" min="1" max="50"
                            class="w-20 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                    </div>
                    <div class="flex items-end gap-2">
                        <x-admin.btn type="submit" variant="info" :disabled="! $botEnabled"
                            class="text-sm font-semibold shadow disabled:cursor-not-allowed disabled:opacity-40">
                            {{ __('site.admin_run_bot') }}
                        </x-admin.btn>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_bot_schedule_hint') }}</span>
                    </div>
                </form>
            </div>

            {{-- ═══ MANUAL GENERATION (ANALYSIS & GUIDE) ═══ --}}
            <div class="admin-card border-amber-200 dark:border-amber-800">
                <div class="border-b border-amber-100 bg-amber-50 px-6 py-4 dark:border-amber-800 dark:bg-amber-500/10">
                    <h3 class="text-base font-semibold text-amber-900 dark:text-amber-300">{{ __('site.admin_manual_generation_title') }}</h3>
                    <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300/80">{{ __('site.admin_manual_generation_desc') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.ai-generations.manual') }}" class="px-6 py-5 space-y-4">
                    @csrf
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-72">
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_source_url_required') }} <span class="text-red-500">*</span></label>
                            <input type="url" name="source_url" required maxlength="2048"
                                placeholder="{{ __('site.admin_source_url_placeholder') }}"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-amber-400 dark:focus:ring-amber-400"
                                value="{{ old('source_url') }}">
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ __('site.admin_source_url_required_hint') }}</p>
                        </div>
                        <div class="flex-1 min-w-60">
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_topic_title_optional') }}</label>
                            <input type="text" name="topic" maxlength="300"
                                placeholder="{{ __('site.admin_topic_placeholder') }}"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-amber-400 dark:focus:ring-amber-400"
                                value="{{ old('topic') }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.common_category') }} <span class="text-red-500">*</span></label>
                            <select name="category_key" required class="rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-amber-400 dark:focus:ring-amber-400">
                                @foreach($categories as $cat)
                                    @php $tr = $cat->translate(app()->getLocale()) ?? $cat->translations->first(); @endphp
                                    <option value="{{ $cat->key }}" @selected(old('category_key') === $cat->key)>{{ $tr?->name ?? $cat->key }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.language') }} <span class="text-red-500">*</span></label>
                            <select name="locale" required class="rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-amber-400 dark:focus:ring-amber-400">
                                @foreach(config('novaranews.locales', ['en']) as $l)
                                    <option value="{{ $l }}" @selected(old('locale', 'en') === $l)>{{ strtoupper($l) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('site.admin_context_optional') }}</label>
                        <textarea name="context" rows="3" maxlength="5000"
                            placeholder="{{ __('site.admin_context_placeholder') }}"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-amber-400 dark:focus:ring-amber-400">{{ old('context') }}</textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-admin.btn type="submit" variant="warning" class="text-sm font-semibold shadow">
                            {{ __('site.admin_generate') }}
                        </x-admin.btn>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('site.admin_queue_hint') }}</span>
                    </div>
                </form>
            </div>

            {{-- ═══ FILTERS ═══ --}}
            <div class="flex flex-wrap items-center gap-3 rounded border border-gray-200 bg-white px-4 py-3">
                {{-- Filtre formu --}}
                <form method="get" class="flex flex-wrap items-center gap-3">
                    <select name="status" class="rounded border-gray-300 text-sm shadow-sm">
                        <option value="">{{ __('site.admin_all_statuses') }}</option>
                        <option value="pending"    @selected($status === 'pending')>{{ __('site.admin_dashboard_ai_pending') }}</option>
                        <option value="processing" @selected($status === 'processing')>{{ __('site.admin_dashboard_ai_processing') }}</option>
                        <option value="done"       @selected($status === 'done')>{{ __('site.admin_ai_done') }}</option>
                        <option value="failed"     @selected($status === 'failed')>{{ __('site.admin_dashboard_ai_failed') }}</option>
                    </select>
                    <select name="locale" class="rounded border-gray-300 text-sm shadow-sm">
                        <option value="">{{ __('site.admin_all_languages') }}</option>
                        @foreach($locales as $l)
                            <option value="{{ $l }}" @selected($locale === $l)>{{ strtoupper($l) }}</option>
                        @endforeach
                    </select>
                    <x-admin.btn type="submit" variant="primary">{{ __('site.common_filter') }}</x-admin.btn>
                    <x-admin.btn :href="route('admin.ai-generations.index')">{{ __('site.common_reset') }}</x-admin.btn>
                </form>

                {{-- Cleanup buttons use separate, non-nested forms. --}}
                <div class="ml-auto flex gap-2">
                    <form method="post" action="{{ route('admin.ai-generations.clear-status') }}"
                          data-confirm="{{ __('site.admin_confirm_clear_pending') }}"
                          onsubmit="return confirm(this.dataset.confirm)">
                        @csrf @method('DELETE')
                        <input type="hidden" name="status" value="pending">
                        <x-admin.btn type="submit" variant="soft-warning">
                            {{ __('site.admin_clear_pending') }}
                        </x-admin.btn>
                    </form>
                    <form method="post" action="{{ route('admin.ai-generations.clear-status') }}"
                          data-confirm="{{ __('site.admin_confirm_clear_failed') }}"
                          onsubmit="return confirm(this.dataset.confirm)">
                        @csrf @method('DELETE')
                        <input type="hidden" name="status" value="failed">
                        <x-admin.btn type="submit" variant="soft-danger">
                            {{ __('site.admin_clear_failed') }}
                        </x-admin.btn>
                    </form>
                </div>
            </div>

            {{-- Bulk Bar --}}
            <div id="bulk-bar" class="hidden items-center gap-3 rounded border border-blue-200 bg-blue-50 px-4 py-2 dark:border-blue-700 dark:bg-blue-500/10">
                <span class="text-sm text-blue-800 dark:text-blue-300"><span id="bulk-count">0</span> {{ __('site.admin_records_selected') }}</span>
                <form id="bulk-delete-form" method="post" action="{{ route('admin.ai-generations.bulk-destroy') }}"
                      onsubmit="return confirmBulkDelete()">
                    @csrf @method('DELETE')
                    <div id="bulk-ids"></div>
                    <x-admin.btn type="submit" variant="danger" class="px-3 py-1 text-xs">{{ __('site.admin_delete_selected') }}</x-admin.btn>
                </form>
                <x-admin.btn type="button" onclick="clearSelection()" class="px-2 py-1 text-xs">{{ __('site.admin_clear_selection') }}</x-admin.btn>
            </div>

            {{-- ═══ LIST ═══ --}}
            <div class="admin-table-wrap">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="w-8 px-4 py-3"><input type="checkbox" id="select-all" class="rounded border-gray-300"></th>
                            <th class="px-4 py-3">{{ __('site.admin_source_title') }}</th>
                            <th class="px-4 py-3">{{ __('site.language') }}</th>
                            <th class="px-4 py-3">{{ __('site.admin_source') }}</th>
                            <th class="px-4 py-3">{{ __('site.admin_status_label') }}</th>
                            <th class="px-4 py-3">{{ __('site.admin_cost') }}</th>
                            <th class="px-4 py-3">{{ __('site.admin_contact_date') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($generations as $gen)
                            <tr class="hover:bg-gray-50" data-id="{{ $gen->id }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="row-checkbox rounded border-gray-300" value="{{ $gen->id }}">
                                </td>
                                <td class="max-w-xs px-4 py-3">
                                    <div class="truncate font-medium text-gray-900">{{ $gen->source_title }}</div>
                                    @if($gen->article)
                                        <div class="truncate text-xs text-gray-500">
                                            → {{ $gen->article->translations->first()?->title ?? '-' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs uppercase text-gray-600">{{ $gen->source_locale }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $gen->newsSource?->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $colors = [
                                            'pending'    => 'bg-yellow-100 text-yellow-800',
                                            'processing' => 'bg-blue-100 text-blue-800',
                                            'done'       => 'bg-green-100 text-green-800',
                                            'failed'     => 'bg-red-100 text-red-800',
                                        ];
                                        $labels = ['pending' => __('site.admin_dashboard_ai_pending'), 'processing' => __('site.admin_dashboard_ai_processing'), 'done' => __('site.admin_ai_done'), 'failed' => __('site.admin_dashboard_ai_failed')];
                                    @endphp
                                    <span class="rounded px-2 py-1 text-xs font-medium {{ $colors[$gen->status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $labels[$gen->status] ?? $gen->status }}
                                    </span>
                                    @if($gen->approved_at)
                                        <span class="ml-1 rounded bg-purple-100 px-1.5 py-0.5 text-xs text-purple-700">{{ __('site.admin_live') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $gen->estimated_cost_usd ? '$'.number_format((float) $gen->estimated_cost_usd, 4) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $gen->created_at->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <x-admin.btn :href="route('admin.ai-generations.show', $gen)" class="px-2 py-1 text-xs">
                                            {{ __('site.admin_contact_view') }}
                                        </x-admin.btn>

                                        @if($gen->isDone() && $gen->article_id && ! $gen->approved_at)
                                            <form action="{{ route('admin.ai-generations.approve', $gen) }}" method="post">
                                                @csrf
                                                <x-admin.btn type="submit" variant="success" class="px-2 py-1">{{ __('site.admin_approve') }}</x-admin.btn>
                                            </form>
                                            @if($gen->article)
                                                <x-admin.btn :href="route('admin.articles.edit', $gen->article)" variant="info" class="px-2 py-1 text-xs">
                                                    {{ __('site.common_edit') }}
                                                </x-admin.btn>
                                            @endif
                                        @endif

                                        @if($gen->isFailed() || $gen->isPending())
                                            <form action="{{ route('admin.ai-generations.retry', $gen) }}" method="post">
                                                @csrf
                                                <x-admin.btn type="submit" variant="warning" class="px-2 py-1">{{ __('site.admin_retry') }}</x-admin.btn>
                                            </form>
                                        @endif

                                        <form action="{{ route('admin.ai-generations.destroy', $gen) }}" method="post"
                                              data-confirm="{{ __('site.admin_confirm_delete_record') }}"
                                              onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf @method('DELETE')
                                            <x-admin.btn type="submit" variant="soft-danger" class="px-2 py-1 text-xs">{{ __('site.common_delete') }}</x-admin.btn>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">{{ __('site.admin_no_records_found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">{{ $generations->links() }}</div>
        </div>
    </div>

    <div id="ai-gen-i18n" hidden data-bulk-delete-suffix="{{ __('site.admin_delete_selected_confirm_suffix') }}"></div>
    <script>
        const selectAll = document.getElementById('select-all');
        const bulkBar   = document.getElementById('bulk-bar');
        const bulkCount = document.getElementById('bulk-count');
        const bulkIds   = document.getElementById('bulk-ids');

        function getChecked() { return [...document.querySelectorAll('.row-checkbox:checked')]; }

        function updateBulkBar() {
            const checked = getChecked();
            bulkBar.classList.toggle('hidden', checked.length === 0);
            bulkBar.classList.toggle('flex', checked.length > 0);
            bulkCount.textContent = checked.length;
        }

        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });

        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                const all = document.querySelectorAll('.row-checkbox');
                selectAll.checked = [...all].every(c => c.checked);
                selectAll.indeterminate = [...all].some(c => c.checked) && ![...all].every(c => c.checked);
                updateBulkBar();
            });
        });

        function clearSelection() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
            selectAll.checked = false;
            selectAll.indeterminate = false;
            updateBulkBar();
        }

        function confirmBulkDelete() {
            const checked = getChecked();
            if (checked.length === 0) return false;
            const i18n = document.getElementById('ai-gen-i18n');
            const suffix = i18n ? i18n.getAttribute('data-bulk-delete-suffix') : '';
            if (!confirm(checked.length + ' ' + suffix)) return false;
            bulkIds.innerHTML = '';
            checked.forEach(cb => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = cb.value;
                bulkIds.appendChild(inp);
            });
            return true;
        }
    </script>
</x-app-layout>
