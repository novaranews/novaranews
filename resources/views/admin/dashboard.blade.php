<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
            {{ __('site.admin_dashboard_title') }}
        </h2>
    </x-slot>

    <div class="admin-page-wrap">
        <div class="admin-shell max-w-7xl">

            {{-- Operations snapshot --}}
            <div class="grid gap-4 sm:grid-cols-3">
                @php
                    $opsStatusClass = match ($opsSnapshot['sitemap_status']) {
                        'pass' => 'text-emerald-600',
                        'warn' => 'text-amber-600',
                        default => 'text-rose-600',
                    };
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pipeline</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $opsSnapshot['pipeline'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Editor reviewed + AI ready</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">SEO Eksik</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">{{ $opsSnapshot['seo_missing'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Meta title/description eksik yayinlar</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sitemap Tazelik</p>
                    <p class="mt-2 text-3xl font-bold {{ $opsStatusClass }}">{{ __('site.admin_readiness_status_'.$opsSnapshot['sitemap_status']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Index, URLs ve News son fetch durumu</p>
                </div>
            </div>

            {{-- Bot snapshot --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_dashboard_bot') }}</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('site.admin_dashboard_bot_enabled') }}:
                        <strong class="{{ $botEnabled ? 'text-green-700' : 'text-red-600' }}">{{ $botEnabled ? __('site.common_yes') : __('site.common_no') }}</strong>
                        · {{ __('site.admin_dashboard_bot_autopublish') }}:
                        <strong>{{ $autoPublish ? __('site.common_yes') : __('site.common_no') }}</strong>
                    </p>
                    <x-admin.btn :href="route('admin.settings.index')" class="mt-3 w-fit">{{ __('site.admin_dashboard_open_settings') }}</x-admin.btn>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_dashboard_quick') }}</h3>
                    <ul class="mt-2 space-y-2 text-sm">
                        <li><x-admin.btn :href="route('admin.articles.create')" class="w-fit">{{ __('site.admin_dashboard_new_article') }}</x-admin.btn></li>
                        <li><x-admin.btn :href="route('admin.ai-generations.index')" class="w-fit">{{ __('site.admin_dashboard_ai_bot') }}</x-admin.btn></li>
                        <li><x-admin.btn :href="route('news-sitemap.locale', ['locale' => config('novaranews.default_locale', 'en')])" target="_blank" rel="noopener" class="w-fit">{{ __('site.admin_dashboard_google_news_xml') }}</x-admin.btn></li>
                    </ul>
                </div>
            </div>

            {{-- Google News readiness --}}
            <div class="admin-card">
                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-gray-800">{{ __('site.admin_readiness_title') }}</h3>
                        @php
                            $statusClasses = match ($readiness['status']) {
                                'pass' => 'bg-emerald-100 text-emerald-700',
                                'warn' => 'bg-amber-100 text-amber-700',
                                default => 'bg-rose-100 text-rose-700',
                            };
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                {{ __('site.admin_readiness_status_'.$readiness['status']) }}
                            </span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $readiness['score'] }}%</span>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('site.admin_readiness_counts', ['pass' => $readiness['counts']['pass'], 'warn' => $readiness['counts']['warn'], 'fail' => $readiness['counts']['fail']]) }}
                    </p>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($readiness['items'] as $item)
                        @php
                            $itemClasses = match ($item['status']) {
                                'pass' => 'bg-emerald-100 text-emerald-700',
                                'warn' => 'bg-amber-100 text-amber-700',
                                default => 'bg-rose-100 text-rose-700',
                            };
                        @endphp
                        <div class="px-4 py-3 sm:flex sm:items-start sm:justify-between sm:gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $itemClasses }}">
                                        {{ __('site.admin_readiness_status_'.$item['status']) }}
                                    </span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['title'] }}</p>
                                </div>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $item['message'] }}</p>
                            </div>
                            <x-admin.btn :href="$item['href']" class="mt-2 shrink-0 px-2 py-1 text-xs sm:mt-0">
                                {{ __('site.admin_readiness_fix') }}
                            </x-admin.btn>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Sitemap health --}}
            <div class="admin-card">
                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_sitemap_health_title') }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_sitemap_health_hint') }}</p>
                </div>
                <div class="grid gap-4 px-4 py-4 md:grid-cols-2">
                    @foreach(['url' => __('site.admin_sitemap_urls_title'), 'news' => __('site.admin_sitemap_news_title')] as $k => $label)
                        @php
                            $item = $sitemapHealth[$k];
                            $statusClasses = match ($item['status']) {
                                'pass' => 'bg-emerald-100 text-emerald-700',
                                'warn' => 'bg-amber-100 text-amber-700',
                                default => 'bg-rose-100 text-rose-700',
                            };
                        @endphp
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $label }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusClasses }}">
                                    {{ __('site.admin_readiness_status_'.$item['status']) }}
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                                {{ __('site.admin_sitemap_health_entries', ['entries' => $item['entries'], 'per_file' => $item['per_file']]) }}
                            </p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ __('site.admin_sitemap_health_pages', ['pages' => $item['pages'], 'expected' => $item['expected_pages']]) }}
                            </p>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-gray-100 px-4 py-3">
                    <div class="flex flex-wrap gap-2">
                        <x-admin.btn :href="$sitemapHealth['links']['index']" target="_blank" rel="noopener" class="w-fit">{{ __('site.admin_sitemap_index_open') }}</x-admin.btn>
                        <x-admin.btn :href="$sitemapHealth['links']['urls_first']" target="_blank" rel="noopener" class="w-fit">{{ __('site.admin_sitemap_urls_first_open') }}</x-admin.btn>
                        <x-admin.btn :href="$sitemapHealth['links']['urls_last']" target="_blank" rel="noopener" class="w-fit">{{ __('site.admin_sitemap_urls_last_open') }}</x-admin.btn>
                        <x-admin.btn :href="$sitemapHealth['links']['news_first']" target="_blank" rel="noopener" class="w-fit">{{ __('site.admin_sitemap_news_first_open') }}</x-admin.btn>
                        <x-admin.btn :href="$sitemapHealth['links']['news_last']" target="_blank" rel="noopener" class="w-fit">{{ __('site.admin_sitemap_news_last_open') }}</x-admin.btn>
                    </div>
                    <div class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                        @php
                            $freshClass = fn (string $state) => match ($state) {
                                'pass' => 'text-emerald-600',
                                'warn' => 'text-amber-600',
                                default => 'text-rose-600',
                            };
                        @endphp
                        <p class="{{ $freshClass($sitemapHealth['freshness']['index']) }}">
                            {{ __('site.admin_sitemap_last_fetch_index', ['time' => $sitemapHealth['last_seen']['index'] ?? __('site.admin_sitemap_last_fetch_never')]) }}
                            @if($sitemapHealth['last_bot']['index'])
                                <span class="ml-1 text-gray-400">({{ $sitemapHealth['last_bot']['index'] }})</span>
                            @endif
                        </p>
                        <p class="{{ $freshClass($sitemapHealth['freshness']['urls']) }}">
                            {{ __('site.admin_sitemap_last_fetch_urls', ['time' => $sitemapHealth['last_seen']['urls'] ?? __('site.admin_sitemap_last_fetch_never')]) }}
                            @if($sitemapHealth['last_bot']['urls'])
                                <span class="ml-1 text-gray-400">({{ $sitemapHealth['last_bot']['urls'] }})</span>
                            @endif
                        </p>
                        <p class="{{ $freshClass($sitemapHealth['freshness']['news']) }}">
                            {{ __('site.admin_sitemap_last_fetch_news', ['time' => $sitemapHealth['last_seen']['news'] ?? __('site.admin_sitemap_last_fetch_never')]) }}
                            @if($sitemapHealth['last_bot']['news'])
                                <span class="ml-1 text-gray-400">({{ $sitemapHealth['last_bot']['news'] }})</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Article stats --}}
            <div class="admin-card">
                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_dashboard_articles') }}</h3>
                </div>
                <div class="grid grid-cols-2 divide-x divide-gray-100 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach([
                        ['label' => __('site.admin_article_stat_total'), 'n' => $stats['articles_total'], 'c' => 'text-gray-800'],
                        ['label' => __('site.admin_article_stat_live'), 'n' => $stats['articles_live'], 'c' => 'text-green-600'],
                        ['label' => __('site.admin_article_stat_scheduled'), 'n' => $stats['articles_scheduled'], 'c' => 'text-amber-600'],
                        ['label' => __('site.admin_article_stat_draft'), 'n' => $stats['articles_draft'], 'c' => 'text-stone-600'],
                        ['label' => __('site.admin_article_stat_pipeline'), 'n' => $stats['articles_pipeline'], 'c' => 'text-indigo-600'],
                        ['label' => 'SEO Eksik', 'n' => $stats['articles_seo_missing'], 'c' => 'text-amber-600'],
                    ] as $row)
                        <div class="px-4 py-4 text-center">
                            <div class="text-2xl font-bold tabular-nums {{ $row['c'] }}">{{ $row['n'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-gray-100 px-4 py-3 text-right">
                    <x-admin.btn :href="route('admin.articles.index')">{{ __('site.admin_dashboard_manage_articles') }}</x-admin.btn>
                </div>
            </div>

            {{-- Last 30 days publish chart --}}
            <div class="admin-card">
                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_chart_last_30_days') }}</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_total') }}: <strong class="text-gray-800 dark:text-gray-100" id="chart-total">-</strong></span>
                </div>
                <div class="px-4 py-4">
                    <canvas id="publish-chart" height="80" aria-label="{{ __('site.admin_chart_last_30_days_aria') }}" role="img"></canvas>
                </div>
            </div>

            {{-- AI + contact --}}
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="overflow-hidden rounded-lg border border-indigo-100 bg-white shadow-sm dark:border-indigo-800 dark:bg-gray-900">
                    <div class="border-b border-indigo-50 bg-indigo-50/80 px-4 py-3 dark:border-indigo-800 dark:bg-indigo-500/10">
                        <h3 class="text-sm font-semibold text-indigo-900 dark:text-indigo-300">{{ __('site.admin_dashboard_ai_queue') }}</h3>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-gray-100">
                        <div class="px-4 py-4 text-center">
                            <div class="text-xl font-bold text-yellow-600">{{ $stats['ai_pending'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_dashboard_ai_pending') }}</div>
                        </div>
                        <div class="px-4 py-4 text-center">
                            <div class="text-xl font-bold text-blue-600">{{ $stats['ai_processing'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_dashboard_ai_processing') }}</div>
                        </div>
                        <div class="px-4 py-4 text-center">
                            <div class="text-xl font-bold text-red-600">{{ $stats['ai_failed'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('site.admin_dashboard_ai_failed') }}</div>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3 text-right">
                        <x-admin.btn :href="route('admin.ai-generations.index')">{{ __('site.admin_dashboard_open_ai') }}</x-admin.btn>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('site.admin_dashboard_contact') }}</h3>
                    </div>
                    <div class="px-4 py-6 text-center">
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['contact_unread'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('site.admin_dashboard_unread_messages') }}</div>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3 text-right">
                        <x-admin.btn :href="route('admin.contact-messages.index')">{{ __('site.admin_dashboard_open_contact') }}</x-admin.btn>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="admin-dashboard-i18n" hidden data-news-label="{{ __('site.admin_news_item_label') }}"></div>
    <script id="admin-dashboard-chart-data" type="application/json">@json($chartData, JSON_UNESCAPED_UNICODE)</script>
    @push('scripts')
    <script>
    (function () {
        var chartDataEl = document.getElementById('admin-dashboard-chart-data');
        var raw = [];
        try {
            raw = chartDataEl ? JSON.parse(chartDataEl.textContent || '[]') : [];
        } catch (e) {
            raw = [];
        }
        var labels = raw.map(function (d) { return d.date.slice(5); }); // MM-DD
        var counts = raw.map(function (d) { return d.count; });
        var total = counts.reduce(function (s, c) { return s + c; }, 0);
        var el = document.getElementById('chart-total');
        if (el) el.textContent = total;

        var canvas = document.getElementById('publish-chart');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var maxVal = Math.max.apply(null, counts) || 1;
        var barW, gap, totalW, startX;

        function drawChart() {
            var dpr = window.devicePixelRatio || 1;
            var cssW = canvas.parentElement.clientWidth - 32;
            var cssH = 80;
            canvas.style.width = cssW + 'px';
            canvas.style.height = cssH + 'px';
            canvas.width = cssW * dpr;
            canvas.height = cssH * dpr;
            ctx.scale(dpr, dpr);
            ctx.clearRect(0, 0, cssW, cssH);

            var n = counts.length;
            gap = 2;
            barW = Math.max(2, (cssW - gap * (n - 1)) / n);
            totalW = barW * n + gap * (n - 1);
            startX = (cssW - totalW) / 2;

            var isDark = document.documentElement.classList.contains('dark');
            var barColor = isDark ? '#38bdf8' : '#1e3a5f';
            var barHover = isDark ? '#7dd3fc' : '#152a45';
            var zeroColor = isDark ? '#374151' : '#e5e7eb';

            counts.forEach(function (count, i) {
                var x = startX + i * (barW + gap);
                var barH = count > 0 ? Math.max(3, Math.round((count / maxVal) * (cssH - 16))) : 2;
                var y = cssH - barH - 2;
                ctx.fillStyle = count > 0 ? barColor : zeroColor;
                ctx.beginPath();
                ctx.roundRect ? ctx.roundRect(x, y, barW, barH, [2, 2, 0, 0]) : ctx.rect(x, y, barW, barH);
                ctx.fill();
            });
        }

        drawChart();
        window.addEventListener('resize', drawChart);

        // Tooltip on hover
        var i18n = document.getElementById('admin-dashboard-i18n');
        var newsLabel = i18n ? i18n.getAttribute('data-news-label') : 'news';
        canvas.addEventListener('mousemove', function (e) {
            var rect = canvas.getBoundingClientRect();
            var mouseX = e.clientX - rect.left;
            var n = counts.length;
            var idx = -1;
            for (var i = 0; i < n; i++) {
                var x = startX + i * (barW + gap);
                if (mouseX >= x && mouseX <= x + barW) { idx = i; break; }
            }
            canvas.title = idx >= 0 ? (labels[idx] + ': ' + counts[idx] + ' ' + newsLabel) : '';
        });
    })();
    </script>
    @endpush
</x-app-layout>
