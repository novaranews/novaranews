import './bootstrap';

const BOOKMARKS_KEY = 'novara-bookmarks';

function normalizeBookmarkUrl(u) {
    try {
        const url = new URL(String(u), window.location.origin);
        url.hash = '';
        const path = url.pathname.replace(/\/+$/, '') || '/';
        return url.origin + path + url.search;
    } catch {
        return String(u);
    }
}

function titleFromUrl(u) {
    try {
        const path = new URL(String(u), window.location.origin).pathname;
        const seg = path.split('/').filter(Boolean).pop() || '';
        return decodeURIComponent(seg.replace(/-/g, ' ')) || String(u);
    } catch {
        return String(u);
    }
}

function loadBookmarkItems() {
    try {
        const raw = JSON.parse(localStorage.getItem(BOOKMARKS_KEY) || '[]');
        if (!Array.isArray(raw)) {
            return [];
        }
        if (raw.length && typeof raw[0] === 'string') {
            return raw.map((url) => ({ url: String(url), title: titleFromUrl(url) }));
        }
        return raw
            .filter((x) => x && (typeof x.url === 'string' || typeof x.url === 'number'))
            .map((x) => ({
                url: String(x.url),
                title: String(x.title || titleFromUrl(x.url)),
                image: String(x.image || ''),
            }));
    } catch {
        return [];
    }
}

const novaraBookmarks = {
    items: loadBookmarkItems(),

    persist() {
        try {
            localStorage.setItem(BOOKMARKS_KEY, JSON.stringify(this.items));
        } catch {
            /* quota */
        }
        try {
            document.dispatchEvent(new CustomEvent('novara-bookmarks-changed'));
        } catch {
            /* ignore */
        }
    },

    has(url) {
        const n = normalizeBookmarkUrl(url);
        return this.items.some((i) => normalizeBookmarkUrl(i.url) === n);
    },

    toggle(entry) {
        const url = typeof entry === 'string' ? entry : entry.url;
        const title =
            typeof entry === 'string' ? titleFromUrl(url) : String(entry.title || titleFromUrl(url));
        const image = typeof entry === 'string' ? '' : String(entry.image || '');
        const n = normalizeBookmarkUrl(url);
        const idx = this.items.findIndex((i) => normalizeBookmarkUrl(i.url) === n);
        if (idx >= 0) {
            this.items.splice(idx, 1);
        } else {
            this.items.unshift({ url: String(url), title, image });
        }
        this.persist();
    },

    remove(url) {
        const n = normalizeBookmarkUrl(url);
        this.items = this.items.filter((i) => normalizeBookmarkUrl(i.url) !== n);
        this.persist();
    },
};

window.novaraBookmarks = novaraBookmarks;

function syncBookmarkButtonEl(btn) {
    const url = btn.getAttribute('data-article-url');
    if (!url) {
        return;
    }
    const saved = novaraBookmarks.has(url);
    const labelSave = btn.getAttribute('data-label-save') || '';
    const labelSaved = btn.getAttribute('data-label-saved') || '';
    btn.setAttribute('aria-label', saved ? labelSaved : labelSave);
    btn.setAttribute('data-saved', saved ? 'true' : 'false');
    const svg = btn.querySelector('svg');
    if (svg) {
        svg.setAttribute('fill', saved ? 'currentColor' : 'none');
    }
}

function initBookmarkButtons() {
    document.querySelectorAll('[data-nv-bookmark]').forEach((btn) => {
        syncBookmarkButtonEl(btn);
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const url = btn.getAttribute('data-article-url');
            const title = btn.getAttribute('data-article-title') || '';
            const image = btn.getAttribute('data-article-image') || '';
            if (url) {
                novaraBookmarks.toggle({ url, title, image });
                syncBookmarkButtonEl(btn);
            }
        });
    });
    document.addEventListener('novara-bookmarks-changed', () => {
        document.querySelectorAll('[data-nv-bookmark]').forEach(syncBookmarkButtonEl);
    });
}

function initReadingProgress() {
    const el = document.getElementById('nv-reading-progress');
    if (!el) {
        return;
    }
    const onScroll = () => {
        const doc = document.documentElement;
        const scrolled = doc.scrollTop;
        const total = doc.scrollHeight - doc.clientHeight;
        const pct = total > 0 ? Math.round((scrolled / total) * 1000) / 10 : 0;
        el.style.width = `${pct}%`;
        el.setAttribute('aria-valuenow', String(Math.round(pct)));
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

function initScrollTopFab() {
    const btn = document.getElementById('nv-scroll-top-fab');
    if (!btn) {
        return;
    }
    function nearBottom() {
        const doc = document.documentElement;
        return window.innerHeight + window.scrollY >= doc.scrollHeight - 120;
    }
    function update() {
        const show = window.scrollY > 400 || nearBottom();
        btn.classList.toggle('hidden', !show);
        btn.setAttribute('aria-hidden', show ? 'false' : 'true');
        if (show) {
            btn.removeAttribute('tabindex');
        } else {
            btn.setAttribute('tabindex', '-1');
        }
    }
    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update, { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

function initBreakingTicker() {
    const root = document.getElementById('nv-breaking-ticker');
    if (!root) {
        return;
    }
    const items = root.querySelectorAll('[data-nv-breaking-item]');
    if (items.length === 0) {
        return;
    }
    let i = 0;
    let timer = null;
    function showOnly(idx) {
        items.forEach((a, j) => {
            a.classList.toggle('hidden', j !== idx);
        });
    }
    function rotate() {
        if (items.length < 2) {
            return;
        }
        i = (i + 1) % items.length;
        showOnly(i);
    }
    function goPrev() {
        if (items.length < 2) {
            return;
        }
        i = (i - 1 + items.length) % items.length;
        showOnly(i);
        schedule();
    }
    function goNext() {
        if (items.length < 2) {
            return;
        }
        rotate();
        schedule();
    }
    function schedule() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
        if (items.length > 1) {
            timer = window.setInterval(rotate, 5500);
        }
    }
    root.querySelector('[data-nv-breaking-prev]')?.addEventListener('click', (e) => {
        e.preventDefault();
        goPrev();
    });
    root.querySelector('[data-nv-breaking-next]')?.addEventListener('click', (e) => {
        e.preventDefault();
        goNext();
    });
    root.addEventListener('mouseenter', () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    });
    root.addEventListener('mouseleave', schedule);
    showOnly(0);
    schedule();
}

function initHorizontalScrollButtons() {
    document.querySelectorAll('[data-nv-hscroll]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-nv-hscroll');
            const dir = parseInt(btn.getAttribute('data-nv-hscroll-dir') || '1', 10);
            const strip = targetId ? document.getElementById(targetId) : null;
            if (strip) {
                strip.scrollBy({ left: dir * strip.clientWidth, behavior: 'smooth' });
            }
        });
    });
}

function initScrollReveal() {
    const nodes = Array.from(document.querySelectorAll('.nv-reveal'));
    if (!nodes.length) {
        return;
    }
    if (!('IntersectionObserver' in window)) {
        nodes.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -6% 0px', threshold: 0.08 }
    );
    nodes.forEach((el) => observer.observe(el));
}

function initCopyButtons() {
    document.querySelectorAll('[data-nv-copy]').forEach((btn) => {
        const url = btn.getAttribute('data-copy-url');
        if (!url) {
            return;
        }
        const defIcon = btn.querySelector('.nv-copy-icon-default');
        const doneIcon = btn.querySelector('.nv-copy-icon-done');
        const labelEl = btn.querySelector('.nv-copy-label');
        const labelCopy = btn.getAttribute('data-label-copy') || '';
        const labelCopied = btn.getAttribute('data-label-copied') || '';
        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(url).then(() => {
                defIcon?.classList.add('hidden');
                doneIcon?.classList.remove('hidden');
                if (labelEl && labelCopied) {
                    labelEl.textContent = labelCopied;
                }
                btn.setAttribute('title', labelCopied);
                btn.classList.add(
                    'border-green-500',
                    'bg-green-500',
                    'text-white',
                    'dark:border-green-500',
                    'dark:bg-green-500'
                );
                window.setTimeout(() => {
                    defIcon?.classList.remove('hidden');
                    doneIcon?.classList.add('hidden');
                    if (labelEl && labelCopy) {
                        labelEl.textContent = labelCopy;
                    }
                    btn.setAttribute('title', labelCopy);
                    btn.classList.remove(
                        'border-green-500',
                        'bg-green-500',
                        'text-white',
                        'dark:border-green-500',
                        'dark:bg-green-500'
                    );
                }, 2500);
            });
        });
    });
}

function initTocToggle() {
    const nav = document.querySelector('[data-nv-toc]');
    if (!nav) {
        return;
    }
    const btn = nav.querySelector('[data-nv-toc-toggle]');
    const panel = nav.querySelector('[data-nv-toc-panel]');
    const icon = nav.querySelector('[data-nv-toc-icon]');
    if (!btn || !panel) {
        return;
    }
    let open = true;
    function setState(o) {
        open = o;
        panel.classList.toggle('hidden', !open);
        icon?.classList.toggle('rotate-180', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    setState(true);
    btn.addEventListener('click', () => setState(!open));
}

function initArticleReadBanner() {
    const banner = document.getElementById('nv-article-read-banner');
    if (!banner) {
        return;
    }
    const onScroll = () => {
        const doc = document.documentElement;
        const scrolled = doc.scrollTop + doc.clientHeight;
        const total = doc.scrollHeight;
        if (scrolled >= total * 0.88) {
            banner.removeAttribute('hidden');
            window.removeEventListener('scroll', onScroll);
        }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
}

function initAdminSidebarNavigation() {
    const openBtn = document.getElementById('nv-admin-sidebar-open');
    const panel = document.getElementById('nv-admin-sidebar-mobile');
    if (!openBtn || !panel) {
        return;
    }

    const closeEls = panel.querySelectorAll('[data-admin-sidebar-close]');
    let lastFocused = null;

    function setOpen(open) {
        panel.classList.toggle('hidden', !open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.documentElement.classList.toggle('overflow-hidden', open);

        if (open) {
            lastFocused = document.activeElement;
            window.setTimeout(() => {
                const focusTarget = panel.querySelector('a,button,select');
                if (focusTarget && typeof focusTarget.focus === 'function') {
                    focusTarget.focus();
                }
            }, 0);
        } else if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    setOpen(false);
    openBtn.addEventListener('click', () => setOpen(true));
    closeEls.forEach((el) => el.addEventListener('click', () => setOpen(false)));
    panel.addEventListener('click', (event) => {
        if (event.target === panel) {
            setOpen(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.classList.contains('hidden')) {
            setOpen(false);
        }
    });
    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 768px)').matches) {
            setOpen(false);
        }
    });
}

function initDropdowns() {
    document.querySelectorAll('[data-nv-dropdown]').forEach((wrap) => {
        const trigger = wrap.querySelector('[data-nv-dropdown-trigger]');
        const panel = wrap.querySelector('[data-nv-dropdown-panel]');
        if (!trigger || !panel) {
            return;
        }
        let open = false;
        function setOpen(v) {
            open = v;
            panel.classList.toggle('hidden', !open);
        }
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            setOpen(!open);
        });
        panel.querySelectorAll('a').forEach((a) => {
            a.addEventListener('click', () => setOpen(false));
        });
        document.addEventListener('click', (e) => {
            if (open && !wrap.contains(e.target)) {
                setOpen(false);
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                setOpen(false);
            }
        });
    });
}

function initModals() {
    document.querySelectorAll('[data-nv-modal]').forEach((modal) => {
        const name = modal.getAttribute('data-nv-modal-name');
        if (!name) {
            return;
        }
        const initial = modal.getAttribute('data-nv-modal-initial') === '1';
        const backdrop = modal.querySelector('[data-nv-modal-backdrop]');
        function setShow(show) {
            modal.classList.toggle('hidden', !show);
            document.body.classList.toggle('overflow-y-hidden', show);
            if (show) {
                const focusEl =
                    modal.querySelector('#password') ||
                    modal.querySelector('input:not([type="hidden"])');
                if (focusEl && typeof focusEl.focus === 'function') {
                    window.setTimeout(() => focusEl.focus(), 50);
                }
            }
        }
        setShow(initial);

        document.addEventListener(
            'nv-modal-open',
            (e) => {
                if (e.detail === name) {
                    setShow(true);
                }
            },
            false
        );
        document.addEventListener(
            'nv-modal-close',
            (e) => {
                if (!e.detail || e.detail === name) {
                    setShow(false);
                }
            },
            false
        );

        modal.querySelectorAll('[data-nv-modal-close]').forEach((el) => {
            el.addEventListener('click', () => setShow(false));
        });
        backdrop?.addEventListener('click', () => setShow(false));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                setShow(false);
            }
        });
    });

    document.querySelectorAll('[data-nv-modal-open]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const name = btn.getAttribute('data-nv-modal-open');
            if (name) {
                document.dispatchEvent(new CustomEvent('nv-modal-open', { detail: name }));
            }
        });
    });
}

function initAdminSettingsTabs() {
    const root = document.querySelector('[data-nv-admin-tabs]');
    if (!root) {
        return;
    }
    const buttons = root.querySelectorAll('[data-nv-admin-tab]');
    const panels = root.querySelectorAll('[data-nv-admin-tab-panel]');
    function show(tab) {
        buttons.forEach((b) => {
            const active = b.getAttribute('data-nv-admin-tab') === tab;
            b.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((p) => {
            const match = p.getAttribute('data-nv-admin-tab-panel') === tab;
            p.hidden = !match;
        });
    }
    buttons.forEach((b) => {
        b.addEventListener('click', () => {
            const t = b.getAttribute('data-nv-admin-tab');
            if (t) {
                show(t);
            }
        });
    });
    show('site');
}

function initProfileFlashMessages() {
    document.querySelectorAll('[data-nv-profile-flash]').forEach((el) => {
        window.setTimeout(() => {
            el.classList.add('hidden');
        }, 2000);
    });
}

function initConfirmSummaries() {
    document.querySelectorAll('[data-confirm-summary]').forEach((el) => {
        const handler = (event) => {
            const msg = el.getAttribute('data-confirm-summary');
            if (msg && !window.confirm(msg)) {
                event.preventDefault();
                event.stopPropagation();
            }
        };
        if (el.tagName === 'FORM') {
            el.addEventListener('submit', handler);
        } else {
            el.addEventListener('click', handler);
        }
    });
}

function syncThemeToggle() {
    const btn = document.getElementById('theme-toggle');
    if (!btn) {
        return;
    }
    const dark = document.documentElement.classList.contains('dark');
    btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
    const labelDark = btn.getAttribute('data-label-to-dark');
    const labelLight = btn.getAttribute('data-label-to-light');
    if (labelDark && labelLight) {
        btn.setAttribute('aria-label', dark ? labelLight : labelDark);
    }
}

function initMobileNav() {
    const panel = document.getElementById('mobile-nav-panel');
    const openBtn = document.getElementById('nv-mobile-nav-open');
    const closeBtn = document.getElementById('nv-mobile-nav-close');
    const backdrop = panel?.querySelector('[data-nv-mobile-nav-backdrop]');
    if (!panel || !openBtn) {
        return;
    }

    function setOpen(open) {
        panel.classList.toggle('hidden', !open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        document.documentElement.classList.toggle('overflow-hidden', open);
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    setOpen(false);

    openBtn.addEventListener('click', () => setOpen(true));
    closeBtn?.addEventListener('click', () => setOpen(false));
    backdrop?.addEventListener('click', () => setOpen(false));

    panel.addEventListener('click', (e) => {
        const a = e.target.closest('a');
        if (a && panel.contains(a)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
            setOpen(false);
        }
    });

    window.addEventListener('pageshow', () => setOpen(false));

    const mq = window.matchMedia('(min-width: 768px)');
    const onViewport = () => {
        if (mq.matches) {
            setOpen(false);
        }
    };
    mq.addEventListener('change', onViewport);
    window.addEventListener('resize', onViewport);
}

/**
 * Cache'lenmiş sayfalarda CSRF token yenile.
 *
 * Sayfa FastCGI veya Cloudflare cache'inden geldiğinde HTML'deki _token inputu
 * başka bir kullanıcıya ait olabilir → 419 hatası.
 * Bu fonksiyon sayfa yüklenince POST /novara-csrf'den güncel token'ı çekip
 * tüm form input[name="_token"] alanlarını ve meta csrf-token etiketini günceller.
 * Çağrı yalnızca sayfada @csrf formu varsa yapılır.
 */
async function refreshCsrfIfNeeded() {
    const tokenInputs = document.querySelectorAll('input[name="_token"]');
    if (!tokenInputs.length) return; // Bu sayfada form yoksa atla

    try {
        const res = await fetch('/novara-csrf', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) return;
        const { token } = await res.json();
        if (!token) return;

        // Tüm @csrf input'larını güncelle (ana form + yanıt formları)
        tokenInputs.forEach((el) => { el.value = token; });

        // Meta csrf-token etiketini güncelle (axios ve diğer kütüphaneler için)
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);

        // Axios kullanılıyorsa header'ı da güncelle
        if (window.axios?.defaults?.headers?.common !== undefined) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    } catch {
        // Sessizce başarısız ol — kullanıcı deneyimi bozulmasın
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initReadingProgress();
    initScrollTopFab();
    initBreakingTicker();
    initScrollReveal();
    initHorizontalScrollButtons();
    initCopyButtons();
    initTocToggle();
    initArticleReadBanner();
    initAdminSidebarNavigation();
    initAdminSettingsTabs();
    initProfileFlashMessages();
    initConfirmSummaries();
    syncThemeToggle();
    initMobileNav();
    document.querySelectorAll('.article-body img').forEach((img) => {
        img.setAttribute('loading', 'lazy');
        img.setAttribute('decoding', 'async');
        if (!img.getAttribute('alt')) {
            img.setAttribute('alt', '');
        }
    });

    // Cache'lenmiş sayfalarda CSRF token'ını yenile (formlar için)
    refreshCsrfIfNeeded();

    const deferInit = () => {
        initBookmarkButtons();
        initDropdowns();
        initModals();
    };
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(deferInit, { timeout: 1200 });
    } else {
        window.setTimeout(deferInit, 250);
    }
});

document.getElementById('theme-toggle')?.addEventListener('click', () => {
    const root = document.documentElement;
    const dark = root.classList.toggle('dark');
    try {
        localStorage.setItem('novara-theme', dark ? 'dark' : 'light');
    } catch {
        /* ignore */
    }
    syncThemeToggle();
});
