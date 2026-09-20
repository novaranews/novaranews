<x-app-layout>
    <x-slot name="header">
        <div class="admin-toolbar">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ __('site.admin_articles_title') }}</h2>
            <div class="flex flex-wrap items-center gap-2">
                <x-admin.btn :href="route('admin.articles.export-json')" :title="__('site.admin_export_json_hint')">
                    {{ __('site.admin_export_json_button') }}
                </x-admin.btn>
                <button onclick="document.getElementById('claude-paste-panel').classList.toggle('hidden')"
                        class="inline-flex items-center rounded-md border border-purple-300 bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-700 transition hover:bg-purple-100 dark:border-purple-700 dark:bg-purple-500/10 dark:text-purple-300 dark:hover:bg-purple-500/20 sm:px-4 sm:py-2 sm:text-sm">
                    {{ __('site.admin_articles_claude_paste') }}
                </button>
                <x-admin.btn type="button" onclick="document.getElementById('import-json-panel').classList.toggle('hidden')">
                    ⬆ {{ __('site.admin_articles_json_import_short') }}
                </x-admin.btn>
                <x-admin.btn :href="route('admin.articles.create')" variant="primary">{{ __('site.admin_new_article') }}</x-admin.btn>
            </div>
        </div>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-7xl">

            {{-- JSON Import Panel --}}
            <div id="import-json-panel" class="hidden mb-6 rounded-lg border border-blue-200 bg-blue-50 p-5 dark:border-blue-800 dark:bg-blue-500/10">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-300">{{ __('site.admin_import_title') }}</h3>
                        <p class="mt-0.5 text-xs text-blue-700 dark:text-blue-300/80">
                            {{ __('site.admin_import_desc') }}
                            <a href="{{ route('admin.articles.import-example') }}" class="underline font-medium">{{ __('site.admin_import_example') }}</a>
                        </p>
                    </div>
                    <button onclick="document.getElementById('import-json-panel').classList.add('hidden')"
                            class="text-lg leading-none text-blue-400 hover:text-blue-600 dark:text-blue-300 dark:hover:text-blue-200">✕</button>
                </div>

                @if($errors->has('import_file'))
                    <div class="mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $errors->first('import_file') }}
                    </div>
                @endif

                <form action="{{ route('admin.articles.import-json') }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-48">
                        <label class="mb-1 block text-xs font-medium text-blue-800 dark:text-blue-300">{{ __('site.admin_import_file_label') }}</label>
                        <input type="file" name="import_file" accept=".json,application/json"
                               class="block w-full rounded border border-blue-300 bg-white px-3 py-1.5 text-sm shadow-sm dark:border-blue-700 dark:bg-gray-800 dark:text-gray-100 file:mr-3 file:border-0 file:bg-blue-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-blue-700 dark:file:bg-blue-500/20 dark:file:text-blue-300">
                    </div>
                    <button type="submit"
                            class="whitespace-nowrap rounded-md bg-blue-700 px-5 py-2 text-sm font-medium text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500">
                        {{ __('site.admin_import_button') }}
                    </button>
                </form>

                <details class="mt-3">
                    <summary class="cursor-pointer text-xs text-blue-600 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">{{ __('site.admin_import_format_q') }}</summary>
                    <pre class="mt-2 overflow-x-auto rounded border border-blue-100 bg-white p-3 text-xs leading-relaxed text-gray-700 dark:border-blue-800 dark:bg-gray-800 dark:text-gray-200">{{ __('site.admin_articles_json_import_example') }}</pre>
                </details>
            </div>

            {{-- Claude Chat Paste Panel --}}
            <div id="claude-paste-panel" class="hidden mb-6 rounded-lg border border-purple-200 bg-purple-50 p-5 dark:border-purple-800 dark:bg-purple-500/10">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-300">{{ __('site.admin_articles_claude_paste_title') }}</h3>
                        <p class="mt-0.5 text-xs text-purple-700 dark:text-purple-300/80">
                            {{ __('site.admin_articles_claude_paste_desc') }}
                        </p>
                    </div>
                    <button onclick="document.getElementById('claude-paste-panel').classList.add('hidden')"
                            class="text-lg leading-none text-purple-400 hover:text-purple-600 dark:text-purple-300 dark:hover:text-purple-200">✕</button>
                </div>

                @if($errors->has('json_text'))
                    <div class="mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $errors->first('json_text') }}
                    </div>
                @endif

                <form action="{{ route('admin.articles.import-json-paste') }}" method="post">
                    @csrf
                    <textarea name="json_text" rows="12"
                              placeholder="{{ __('site.admin_articles_json_paste_placeholder') }}"
                              class="w-full rounded border border-purple-300 bg-white px-3 py-2 font-mono text-xs text-gray-800 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-400 dark:border-purple-700 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-purple-400 dark:focus:ring-purple-400"
                              >{{ old('json_text') }}</textarea>
                    <div class="mt-3 flex items-center gap-3">
                        <button type="submit"
                                class="rounded-md bg-purple-700 px-5 py-2 text-sm font-medium text-white hover:bg-purple-600">
                            {{ __('site.admin_import_button') }}
                        </button>
                        <span class="text-xs text-purple-600 dark:text-purple-300">{{ __('site.admin_articles_saved_as_draft_hint') }}</span>
                    </div>
                </form>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-2 text-red-800">{{ session('error') }}</div>
            @endif
            @if($googleIndexingAdmin['feature_on'] ?? false)
                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('site.admin_google_index_quota_line', [
                        'used' => max(0, ($googleIndexingAdmin['limit'] ?? 0) - ($googleIndexingAdmin['remaining'] ?? 0)),
                        'limit' => $googleIndexingAdmin['limit'] ?? 0,
                        'remaining' => $googleIndexingAdmin['remaining'] ?? 0,
                    ]) }}
                    @if(!($googleIndexingAdmin['configured'] ?? false))
                        <span class="text-amber-600 dark:text-amber-400"> — {{ __('site.admin_google_index_not_configured') }}</span>
                    @endif
                </p>
            @endif

            <div class="mb-5 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_article_stats_title') }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_article_stats_hint') }}</p>
                </div>
                <div class="grid grid-cols-2 divide-x divide-gray-100 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach([
                        ['label' => __('site.admin_article_stat_total'), 'count' => $stats['total'], 'color' => 'text-gray-800'],
                        ['label' => __('site.admin_article_stat_live'), 'count' => $stats['live'], 'color' => 'text-green-600'],
                        ['label' => __('site.admin_article_stat_scheduled'), 'count' => $stats['scheduled'], 'color' => 'text-amber-600'],
                        ['label' => __('site.admin_article_stat_draft'), 'count' => $stats['draft'], 'color' => 'text-stone-600'],
                        ['label' => __('site.admin_article_stat_pipeline'), 'count' => $stats['pipeline'], 'color' => 'text-indigo-600'],
                    ] as $stat)
                        <div class="px-4 py-3 text-center">
                            <div class="text-xl font-bold tabular-nums {{ $stat['color'] }}">{{ $stat['count'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="get" class="mb-4 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:grid-cols-6">
                <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('site.admin_search_title_or_slug') }}" class="rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 sm:col-span-2">
                <select name="category_id" class="rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">{{ __('site.admin_all_categories') }}</option>
                    @foreach($categories as $cat)
                        @php $cn = $cat->translate('en')?->name ?? $cat->key; @endphp
                        <option value="{{ $cat->id }}" @selected((string) request('category_id') === (string) $cat->id)>{{ $cn }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">{{ __('site.admin_all_statuses') }}</option>
                    <option value="published" @selected($status === 'published')>{{ __('site.admin_published') }}</option>
                    <option value="editor_reviewed" @selected($status === 'editor_reviewed')>{{ __('site.admin_editor_reviewed') }}</option>
                    <option value="ai_ready" @selected($status === 'ai_ready')>{{ __('site.admin_ai_ready') }}</option>
                    <option value="draft" @selected($status === 'draft')>{{ __('site.admin_article_stat_draft') }}</option>
                </select>
                <select name="locale" class="rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" title="{{ __('site.admin_article_language') }}">
                    <option value="">{{ __('site.admin_all_languages') }}</option>
                    @foreach(config('novaranews.locales') as $l)
                        <option value="{{ $l }}" @selected($locale === $l)>{{ strtoupper($l) }}</option>
                    @endforeach
                </select>
                <select name="content_type" class="rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">All types</option>
                    @foreach(\App\Models\Article::CONTENT_TYPES as $ct)
                        <option value="{{ $ct }}" @selected($contentType === $ct)>{{ ucfirst($ct) }}</option>
                    @endforeach
                </select>
                <input type="date" name="published_from" value="{{ $publishedFrom }}" class="rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" title="{{ __('site.admin_published_from') }}">
                <input type="date" name="published_to" value="{{ $publishedTo }}" class="rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" title="{{ __('site.admin_published_to') }}">
                <div class="flex gap-2 sm:col-span-6">
                    <x-admin.btn type="submit" variant="primary">{{ __('site.common_filter') }}</x-admin.btn>
                    <x-admin.btn :href="route('admin.articles.index')">{{ __('site.common_reset') }}</x-admin.btn>
                </div>
            </form>
            <form id="bulk-status-form" action="{{ route('admin.articles.bulk-status') }}" method="post" class="mb-4 flex flex-wrap items-center gap-2 rounded border border-gray-200 bg-white p-4">
                @csrf
                <span class="text-sm font-medium text-gray-700">{{ __('site.admin_bulk_action') }}</span>
                <select name="status" class="rounded border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="published">{{ __('site.admin_mark_as_published') }}</option>
                    <option value="editor_reviewed">{{ __('site.admin_mark_as_editor_reviewed') }}</option>
                    <option value="ai_ready">{{ __('site.admin_mark_as_ai_ready') }}</option>
                    <option value="draft">{{ __('site.admin_mark_as_draft') }}</option>
                </select>
                <x-admin.btn type="submit" variant="info">{{ __('site.admin_apply_to_selected') }}</x-admin.btn>
            </form>
            <form id="bulk-destroy-form" action="{{ route('admin.articles.bulk-destroy') }}" method="post" class="mb-4 flex flex-wrap items-center gap-2 rounded border border-red-100 bg-red-50 p-4" data-confirm-delete="{{ e(__('site.admin_delete_selected_articles_permanently')) }}">
                @csrf
                <span class="text-sm font-medium text-red-900">{{ __('site.admin_delete_selected') }}</span>
                <x-admin.btn type="submit" variant="danger">{{ __('site.admin_delete_permanently') }}</x-admin.btn>
            </form>
            <p class="mb-4 text-xs text-gray-500">{{ __('site.admin_bulk_status_hint') }}</p>
            <div class="admin-table-wrap">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                <input type="checkbox" id="toggle-all-articles">
                            </th>
                            <th class="px-2 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_featured_image_short') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_title_label') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.common_category') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.language') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_status_label') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ __('site.admin_published') }}</th>
                            <th class="px-2 py-3 text-center text-xs font-medium uppercase text-gray-500" title="{{ __('site.admin_google_index_col_hint') }}">{{ __('site.admin_google_index_col') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($articles as $article)
                            @php
                                $t = $article->translations->firstWhere('locale', $article->locale) ?? $article->translations->first();
                                $thumbPath = $article->featuredImageThumb ?? $article->getRawOriginal('featured_image');
                                $thumbUrl = $thumbPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($thumbPath) : null;
                                $categoryName = $article->category?->translate(app()->getLocale())?->name
                                    ?? $article->category?->translate('en')?->name
                                    ?? $article->category?->key;
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="ids[]" value="{{ $article->id }}" form="bulk-status-form" class="article-check">
                                </td>
                                <td class="px-2 py-2">
                                    @if($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="{{ $article->featured_image_alt ?: ($t?->title ?? '') }}"
                                             class="h-10 w-16 rounded object-cover ring-1 ring-gray-200 dark:ring-gray-700"
                                             loading="lazy" decoding="async">
                                    @else
                                        <div class="flex h-10 w-16 items-center justify-center rounded bg-gray-100 text-[10px] text-gray-400 dark:bg-gray-800 dark:text-gray-500">—</div>
                                    @endif
                                </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $t?->title ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $categoryName ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm font-mono uppercase text-gray-600">{{ $article->locale }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($article->status === 'published' && $article->published_at && $article->published_at->isFuture())
                                        <span class="rounded bg-amber-100 px-2 py-0.5 font-medium text-amber-900">{{ __('site.admin_article_stat_scheduled') }}</span>
                                    @else
                                        <select
                                            class="js-inline-status rounded border-gray-300 py-0.5 text-xs shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                            data-article-id="{{ $article->id }}"
                                            data-csrf="{{ csrf_token() }}"
                                            data-url="{{ route('admin.articles.bulk-status') }}"
                                        >
                                            @foreach(['published' => __('site.admin_published'), 'editor_reviewed' => __('site.admin_editor_reviewed'), 'ai_ready' => __('site.admin_ai_ready'), 'draft' => __('site.admin_article_stat_draft')] as $val => $label)
                                                <option value="{{ $val }}" @selected($article->status === $val)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $article->published_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="px-2 py-3 text-center text-sm">
                                    @if($googleIndexingAdmin['feature_on'] ?? false)
                                        @if($article->googleIndexingEligible())
                                            @if(($googleIndexingAdmin['configured'] ?? false) && ($googleIndexingAdmin['remaining'] ?? 0) > 0 && $article->googleIndexingNeedsNotify())
                                                <form method="post" action="{{ route('admin.articles.google-indexing', $article) }}" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="rounded border border-emerald-300 bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-800 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-900/50"
                                                            title="{{ __('site.admin_google_index_notify_hint') }}">
                                                        {{ __('site.admin_google_index_notify') }}
                                                    </button>
                                                </form>
                                            @elseif($article->googleIndexingNeedsNotify())
                                                <button type="button" disabled
                                                        class="cursor-not-allowed rounded border border-gray-200 bg-gray-100 px-2 py-1 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
                                                        title="{{ !($googleIndexingAdmin['configured'] ?? false) ? __('site.admin_google_index_not_configured') : __('site.admin_google_index_limit', ['limit' => $googleIndexingAdmin['limit'] ?? 0]) }}">
                                                    {{ __('site.admin_google_index_notify') }}
                                                </button>
                                            @else
                                                <span class="inline-flex cursor-default items-center justify-center rounded border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-800/80 dark:text-gray-400"
                                                      title="{{ __('site.admin_google_index_wait_edit') }}">✓</span>
                                            @endif
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <x-admin.btn :href="route('admin.articles.edit', $article)" class="px-2 py-1 text-xs">{{ __('site.common_edit') }}</x-admin.btn>
                                    <form action="{{ route('admin.articles.destroy', $article) }}" method="post" class="inline ms-2 js-confirm-delete" data-confirm="{{ e(__('site.admin_delete_this_article')) }}">
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
            <div class="mt-4">{{ $articles->links() }}</div>
        </div>
    </div>

    <div id="admin-articles-i18n" hidden data-select-at-least-one="{{ __('site.admin_select_at_least_one_article') }}"></div>
    @once
        @push('scripts')
            <script>
                // Inline status update.
                document.querySelectorAll('.js-inline-status').forEach(function (sel) {
                    sel.addEventListener('change', function () {
                        const id = sel.getAttribute('data-article-id');
                        const csrf = sel.getAttribute('data-csrf');
                        const url = sel.getAttribute('data-url');
                        const status = sel.value;
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        form.style.display = 'none';
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden'; csrfInput.name = '_token'; csrfInput.value = csrf;
                        const statusInput = document.createElement('input');
                        statusInput.type = 'hidden'; statusInput.name = 'status'; statusInput.value = status;
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden'; idInput.name = 'ids[]'; idInput.value = id;
                        form.appendChild(csrfInput);
                        form.appendChild(statusInput);
                        form.appendChild(idInput);
                        document.body.appendChild(form);
                        form.submit();
                    });
                });
                const bulkForm = document.querySelector('form[action$="bulk-status"]');
                const bulkDestroyForm = document.getElementById('bulk-destroy-form');
                const toggleAll = document.getElementById('toggle-all-articles');
                const checkboxes = Array.from(document.querySelectorAll('.article-check'));
                if (toggleAll) {
                    toggleAll.addEventListener('change', function () {
                        checkboxes.forEach(function (cb) { cb.checked = toggleAll.checked; });
                    });
                }
                document.querySelectorAll('form.js-confirm-delete').forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        if (!confirm(form.getAttribute('data-confirm'))) {
                            e.preventDefault();
                        }
                    });
                });
                if (bulkForm) {
                    bulkForm.addEventListener('submit', function (e) {
                        const i18n = document.getElementById('admin-articles-i18n');
                        const selectAtLeastOne = i18n ? i18n.getAttribute('data-select-at-least-one') : '';
                        const anyChecked = checkboxes.some(function (cb) { return cb.checked; });
                        if (!anyChecked) {
                            e.preventDefault();
                            alert(selectAtLeastOne);
                        }
                    });
                }
                if (bulkDestroyForm) {
                    bulkDestroyForm.addEventListener('submit', function (e) {
                        const i18n = document.getElementById('admin-articles-i18n');
                        const selectAtLeastOne = i18n ? i18n.getAttribute('data-select-at-least-one') : '';
                        e.preventDefault();
                        const checked = checkboxes.filter(function (cb) { return cb.checked; });
                        if (!checked.length) {
                            alert(selectAtLeastOne);
                            return;
                        }
                        if (!confirm(bulkDestroyForm.getAttribute('data-confirm-delete'))) {
                            return;
                        }
                        bulkDestroyForm.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
                        checked.forEach(function (cb) {
                            const h = document.createElement('input');
                            h.type = 'hidden';
                            h.name = 'ids[]';
                            h.value = cb.value;
                            bulkDestroyForm.appendChild(h);
                        });
                        bulkDestroyForm.submit();
                    });
                }
            </script>
        @endpush
    @endonce
</x-app-layout>
