/**
 * Header favorites popover — app.js (Vite) yüklenmese de çalışır.
 * app.js ile aynı localStorage anahtarı: novara-bookmarks
 */
(function () {
    var KEY = 'novara-bookmarks';

    function normalizeUrl(u) {
        try {
            var url = new URL(String(u), window.location.origin);
            url.hash = '';
            var path = url.pathname.replace(/\/+$/, '') || '/';
            return url.origin + path + url.search;
        } catch (e) {
            return String(u);
        }
    }

    function parseItems() {
        try {
            var raw = JSON.parse(localStorage.getItem(KEY) || '[]');
            if (!Array.isArray(raw)) {
                return [];
            }
            if (raw.length && typeof raw[0] === 'string') {
                return raw.map(function (url) {
                    return { url: String(url), title: String(url) };
                });
            }
            return raw
                .filter(function (x) {
                    return x && (typeof x.url === 'string' || typeof x.url === 'number');
                })
                .map(function (x) {
                    return {
                        url: String(x.url),
                        title: String(x.title || x.url),
                        image: String(x.image || ''),
                    };
                });
        } catch (e) {
            return [];
        }
    }

    function saveItems(items) {
        try {
            localStorage.setItem(KEY, JSON.stringify(items));
        } catch (e) {}
        try {
            document.dispatchEvent(new CustomEvent('novara-bookmarks-changed'));
        } catch (e) {}
    }

    function removeBookmark(url) {
        var n = normalizeUrl(url);
        var items = parseItems().filter(function (i) {
            return normalizeUrl(i.url) !== n;
        });
        saveItems(items);
    }

    function render() {
        var items = parseItems();
        var ul = document.getElementById('nv-fav-list');
        var empty = document.getElementById('nv-fav-empty');
        var badge = document.getElementById('nv-fav-badge');
        if (!ul || !empty) {
            return;
        }
        while (ul.firstChild) {
            ul.removeChild(ul.firstChild);
        }
        if (items.length === 0) {
            empty.classList.remove('hidden');
            ul.classList.add('hidden');
            if (badge) {
                badge.classList.add('hidden');
                badge.classList.remove('inline-flex');
                badge.textContent = '';
            }
            return;
        }
        empty.classList.add('hidden');
        ul.classList.remove('hidden');
        if (badge) {
            badge.classList.remove('hidden');
            badge.classList.add('inline-flex');
            badge.textContent = items.length > 9 ? '9+' : String(items.length);
        }

        var removeLabel = ul.getAttribute('data-remove-label') || '';

        items.forEach(function (item) {
            var li = document.createElement('li');
            li.className =
                'flex items-start gap-2 border-b border-stone-50 px-2 py-2 last:border-0 dark:border-stone-800';

            var a = document.createElement('a');
            a.href = item.url;
            a.className =
                'flex min-w-0 flex-1 items-start gap-2 text-sm font-medium leading-snug text-stone-800 hover:text-novara-800 dark:text-stone-200 dark:hover:text-sky-400';

            if (item.image) {
                var img = document.createElement('img');
                img.src = item.image;
                img.alt = '';
                img.loading = 'lazy';
                img.decoding = 'async';
                img.className =
                    'h-12 w-16 shrink-0 rounded object-cover bg-stone-100 dark:bg-stone-800';
                a.appendChild(img);
            }

            var titleSpan = document.createElement('span');
            titleSpan.className = 'line-clamp-2 min-w-0';
            titleSpan.textContent = item.title;
            a.appendChild(titleSpan);
            a.addEventListener('click', function () {
                var det = a.closest('details');
                if (det) {
                    det.removeAttribute('open');
                }
            });

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'nv-icon-btn-sm shrink-0 hover:text-red-600 dark:hover:text-red-400';
            btn.setAttribute('aria-label', removeLabel);
            btn.innerHTML =
                '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                removeBookmark(item.url);
            });

            li.appendChild(a);
            li.appendChild(btn);
            ul.appendChild(li);
        });
    }

    document.addEventListener('DOMContentLoaded', render);
    window.addEventListener('storage', function (e) {
        if (e.key === KEY) {
            render();
        }
    });
    document.addEventListener('novara-bookmarks-changed', render);
})();
