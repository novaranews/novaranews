(function () {
    var locale = document.documentElement.lang
        ? document.documentElement.lang.split('-')[0]
        : 'en';

    var tools = [
        {
            name: 'search_articles',
            title: 'Search Articles',
            description: 'Search Novara News articles by keyword. Navigates to the search results page.',
            inputSchema: {
                type: 'object',
                properties: {
                    query: { type: 'string', description: 'Search keywords' }
                },
                required: ['query']
            },
            annotations: { readOnlyHint: false },
            execute: async function (input) {
                var url = '/' + locale + '/search?q=' + encodeURIComponent(input.query || '');
                window.location.href = url;
                return [{ type: 'text', text: JSON.stringify({ navigated: true, url: url }) }];
            }
        },
        {
            name: 'get_page_info',
            title: 'Get Page Info',
            description: 'Return metadata about the current page: title, description, canonical URL, language, and Open Graph image.',
            inputSchema: { type: 'object', properties: {} },
            annotations: { readOnlyHint: true },
            execute: async function () {
                var getMeta = function (name) {
                    var el = document.querySelector('meta[name="' + name + '"], meta[property="' + name + '"]');
                    return el ? el.getAttribute('content') : null;
                };
                return [{ type: 'text', text: JSON.stringify({
                    title: document.title,
                    description: getMeta('description'),
                    canonical: (document.querySelector('link[rel="canonical"]') || {}).href || location.href,
                    locale: locale,
                    og_image: getMeta('og:image'),
                    og_type: getMeta('og:type')
                }) }];
            }
        },
        {
            name: 'get_rss_feed_url',
            title: 'Get RSS Feed URL',
            description: 'Return the RSS feed URL for a given language.',
            inputSchema: {
                type: 'object',
                properties: {
                    lang: { type: 'string', enum: ['en', 'tr', 'de', 'fr', 'es'], description: 'Language code' }
                }
            },
            annotations: { readOnlyHint: true },
            execute: async function (input) {
                var lang = input.lang || locale;
                return [{ type: 'text', text: JSON.stringify({ url: window.location.origin + '/' + lang + '/feed.xml', type: 'application/rss+xml', language: lang }) }];
            }
        },
        {
            name: 'get_article_content',
            title: 'Get Article Content',
            description: 'Extract the main article text from the current page if it is an article page.',
            inputSchema: { type: 'object', properties: {} },
            annotations: { readOnlyHint: true },
            execute: async function () {
                var articleEl = document.querySelector('article');
                if (!articleEl) return [{ type: 'text', text: JSON.stringify({ error: 'No article found on this page' }) }];
                var headingEl = articleEl.querySelector('h1');
                var paragraphs = Array.prototype.slice.call(articleEl.querySelectorAll('p'))
                    .map(function (p) { return p.innerText.trim(); }).filter(Boolean);
                var publishedEl = document.querySelector('time[datetime]');
                return [{ type: 'text', text: JSON.stringify({
                    heading: headingEl ? headingEl.innerText.trim() : null,
                    published_at: publishedEl ? publishedEl.getAttribute('datetime') : null,
                    text: paragraphs.join('\n\n'),
                    word_count: paragraphs.join(' ').split(/\s+/).length
                }) }];
            }
        },
        {
            name: 'navigate_to_category',
            title: 'Navigate to Category',
            description: 'Navigate to a category page on this site.',
            inputSchema: {
                type: 'object',
                properties: {
                    url: { type: 'string', description: 'Full category URL on this site' }
                },
                required: ['url']
            },
            annotations: { readOnlyHint: false },
            execute: async function (input) {
                var target;
                try { target = new URL(input.url, window.location.origin); } catch (e) {}
                if (!target || target.origin !== window.location.origin) {
                    return [{ type: 'text', text: JSON.stringify({ error: 'URL must be on this site' }) }];
                }
                window.location.href = target.href;
                return [{ type: 'text', text: JSON.stringify({ navigated: true, url: target.href }) }];
            }
        }
    ];

    function doRegister(mc) {
        if (!mc) return;

        // provideContext() — batch registration (older spec)
        if (typeof mc.provideContext === 'function') {
            try { mc.provideContext({ tools: tools }); } catch (e) {}
        }

        // registerTool() — per-tool registration (current spec)
        if (typeof mc.registerTool === 'function') {
            tools.forEach(function (tool) {
                try {
                    mc.registerTool(
                        {
                            name: tool.name,
                            title: tool.title,
                            description: tool.description,
                            inputSchema: tool.inputSchema,
                            annotations: tool.annotations
                        },
                        tool.execute
                    );
                } catch (e) {
                    // Fallback: pass tool as single object (Chrome M136 style)
                    try { mc.registerTool(tool); } catch (e2) {}
                }
            });
        }
    }

    // If already available, register now
    if (navigator.modelContext) {
        doRegister(navigator.modelContext);
        return;
    }

    // Watch for navigator.modelContext being set later (by browser or audit tool)
    try {
        var _mc = undefined;
        Object.defineProperty(navigator, 'modelContext', {
            configurable: true,
            enumerable: true,
            get: function () { return _mc; },
            set: function (v) {
                _mc = v;
                if (v) doRegister(v);
            }
        });
    } catch (e) {
        // defineProperty failed (e.g. already non-configurable); try on load events
        window.addEventListener('DOMContentLoaded', function () {
            if (navigator.modelContext) doRegister(navigator.modelContext);
        });
        window.addEventListener('load', function () {
            if (navigator.modelContext) doRegister(navigator.modelContext);
        });
    }
})();
