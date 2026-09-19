<script>
    (function () {
        function textWidthFactory() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            return function (text, font) {
                if (!ctx) return text.length * 8;
                ctx.font = font;
                return Math.round(ctx.measureText(text).width);
            };
        }

        function stateClass(state, labels) {
            if (state === labels.good) return 'text-emerald-700';
            if (state === labels.long) return 'text-amber-700';
            return 'text-rose-700';
        }

        function state(n, min, max, labels) {
            return n < min ? labels.short : (n > max ? labels.long : labels.good);
        }

        window.initGenericSeoPreview = function (options) {
            const cfg = options || {};
            const selector = cfg.selector || '[data-seo-preview]';
            const titleField = cfg.titleField || 'name';

            document.querySelectorAll(selector).forEach(function (box) {
                const section = box.closest('fieldset');
                if (!section) return;
                const q = function (sel) { return section.querySelector(sel); };
                const titleSourceInput = q('input[name$="[' + titleField + ']"]');
                const slugInput = q('input[name$="[slug]"]');
                const metaTitleInput = q('input[name$="[meta_title]"]');
                const metaDescInput = q('input[name$="[meta_description]"]');
                const ogTitleInput = q('input[name$="[og_title]"]');
                const ogDescInput = q('input[name$="[og_description]"]');
                const canonicalInput = q('input[name$="[canonical_url]"]');
                const noindexInput = q('input[name$="[robots_noindex]"][type="checkbox"]');
                const nofollowInput = q('input[name$="[robots_nofollow]"][type="checkbox"]');

                const outMetaTitle = box.querySelector('[data-preview-meta-title]');
                const outCanonical = box.querySelector('[data-preview-canonical]');
                const outMetaDesc = box.querySelector('[data-preview-meta-description]');
                const outMetaTitleLen = box.querySelector('[data-preview-meta-title-len]');
                const outMetaDescLen = box.querySelector('[data-preview-meta-description-len]');
                const outMetaTitlePx = box.querySelector('[data-preview-meta-title-px]');
                const outMetaDescPx = box.querySelector('[data-preview-meta-description-px]');
                const outMetaTitleBar = box.querySelector('[data-preview-meta-title-bar]');
                const outMetaDescBar = box.querySelector('[data-preview-meta-description-bar]');
                const outOgTitle = box.querySelector('[data-preview-og-title]');
                const outOgDesc = box.querySelector('[data-preview-og-description]');
                const outRobots = box.querySelector('[data-preview-robots]');
                const outCtr = box.querySelector('[data-preview-ctr]');

                const labels = {
                    title: box.dataset.lblTitle || 'Meta title length',
                    description: box.dataset.lblDescription || 'Meta description length',
                    good: box.dataset.lblGood || 'good',
                    short: box.dataset.lblShort || 'too short',
                    long: box.dataset.lblLong || 'too long'
                };
                const pxLabels = {
                    title: box.dataset.lblTitlePx || 'Meta title width',
                    description: box.dataset.lblDescriptionPx || 'Meta description width'
                };
                const ctrLabels = {
                    label: box.dataset.lblCtrLabel || 'CTR suggestion',
                    ok: box.dataset.lblCtrOk || 'Looks good.',
                    titleSpecific: box.dataset.lblCtrTitleSpecific || 'Title feels broad - add a specific term.',
                    titleNoNumber: box.dataset.lblCtrTitleNoNumber || 'Consider adding a concrete number or metric.',
                    descValue: box.dataset.lblCtrDescValue || 'Description should state user value more clearly.'
                };
                const textWidth = textWidthFactory();

                const render = function () {
                    const fallbackTitle = (titleSourceInput && titleSourceInput.value ? titleSourceInput.value : '').trim();
                    const fallbackDesc = (metaDescInput && metaDescInput.value ? metaDescInput.value : '').trim();
                    const canonical = (canonicalInput && canonicalInput.value ? canonicalInput.value : '').trim() || ((slugInput && slugInput.value) ? '/' + slugInput.value.trim() : '/');
                    const metaTitle = (metaTitleInput && metaTitleInput.value ? metaTitleInput.value : '').trim() || fallbackTitle || '...';
                    const metaDesc = (metaDescInput && metaDescInput.value ? metaDescInput.value : '').trim() || '...';
                    const ogTitle = (ogTitleInput && ogTitleInput.value ? ogTitleInput.value : '').trim() || metaTitle;
                    const ogDesc = (ogDescInput && ogDescInput.value ? ogDescInput.value : '').trim() || fallbackDesc || metaDesc;
                    const robots = ((noindexInput && noindexInput.checked) ? 'noindex' : 'index') + ', ' + ((nofollowInput && nofollowInput.checked) ? 'nofollow' : 'follow');

                    if (outMetaTitle) outMetaTitle.textContent = metaTitle;
                    if (outCanonical) outCanonical.textContent = canonical;
                    if (outMetaDesc) outMetaDesc.textContent = metaDesc;
                    if (outOgTitle) outOgTitle.textContent = ogTitle;
                    if (outOgDesc) outOgDesc.textContent = ogDesc;
                    if (outRobots) outRobots.textContent = 'robots: ' + robots;

                    const titleLen = metaTitle.length;
                    const descLen = metaDesc.length;
                    const titleState = state(titleLen, 30, 60, labels);
                    const descState = state(descLen, 70, 160, labels);
                    const titlePx = textWidth(metaTitle, '400 20px Arial');
                    const descPx = textWidth(metaDesc, '400 13px Arial');
                    const titlePxState = state(titlePx, 300, 580, labels);
                    const descPxState = state(descPx, 400, 920, labels);

                    if (outMetaTitleLen) {
                        outMetaTitleLen.textContent = labels.title + ': ' + titleLen + ' (' + titleState + ')';
                        outMetaTitleLen.className = 'mt-1 text-[11px] font-medium ' + stateClass(titleState, labels);
                    }
                    if (outMetaDescLen) {
                        outMetaDescLen.textContent = labels.description + ': ' + descLen + ' (' + descState + ')';
                        outMetaDescLen.className = 'mt-1 text-[11px] font-medium ' + stateClass(descState, labels);
                    }
                    if (outMetaTitlePx) {
                        outMetaTitlePx.textContent = pxLabels.title + ': ' + titlePx + 'px (' + titlePxState + ')';
                        outMetaTitlePx.className = 'mt-1 text-[11px] font-medium ' + stateClass(titlePxState, labels);
                    }
                    if (outMetaDescPx) {
                        outMetaDescPx.textContent = pxLabels.description + ': ' + descPx + 'px (' + descPxState + ')';
                        outMetaDescPx.className = 'mt-1 text-[11px] font-medium ' + stateClass(descPxState, labels);
                    }
                    if (outMetaTitleBar) {
                        outMetaTitleBar.style.width = Math.min(Math.round((titleLen / 80) * 100), 100) + '%';
                        outMetaTitleBar.className = 'h-1.5 rounded transition-all ' + (titleState === labels.good ? 'bg-emerald-500' : (titleState === labels.long ? 'bg-amber-500' : 'bg-rose-500'));
                    }
                    if (outMetaDescBar) {
                        outMetaDescBar.style.width = Math.min(Math.round((descLen / 220) * 100), 100) + '%';
                        outMetaDescBar.className = 'h-1.5 rounded transition-all ' + (descState === labels.good ? 'bg-emerald-500' : (descState === labels.long ? 'bg-amber-500' : 'bg-rose-500'));
                    }

                    if (outCtr) {
                        const hints = [];
                        if (titleLen < 40) hints.push(ctrLabels.titleSpecific);
                        if (!/\d/.test(metaTitle)) hints.push(ctrLabels.titleNoNumber);
                        if (descLen < 100) hints.push(ctrLabels.descValue);
                        const ctrMessage = hints.length ? hints[0] : ctrLabels.ok;
                        outCtr.textContent = ctrLabels.label + ': ' + ctrMessage;
                        outCtr.className = 'mt-2 text-xs ' + (hints.length ? 'text-amber-700' : 'text-emerald-700');
                    }
                };

                [
                    titleSourceInput, slugInput, metaTitleInput, metaDescInput, ogTitleInput, ogDescInput, canonicalInput, noindexInput, nofollowInput
                ].filter(Boolean).forEach(function (el) {
                    el.addEventListener(el.type === 'checkbox' ? 'change' : 'input', render);
                });
                render();
            });
        };

        window.initArticleSeoPreview = function () {
            const box = document.querySelector('[data-article-seo-preview]');
            if (!box) return;
            const titleInput = document.getElementById('meta_title');
            const fallbackTitleInput = document.getElementById('title');
            const descInput = document.getElementById('meta_description');
            const typeInput = document.getElementById('content_type');
            const canonicalInput = document.getElementById('canonical_url');
            const ogTitleInput = document.getElementById('og_title');
            const ogDescInput = document.getElementById('og_description');
            const noindexInput = document.querySelector('input[name="translation[robots_noindex]"][type="checkbox"]');
            const featuredImageInput = document.getElementById('featured_image');
            const featuredAltInput = document.getElementById('featured_image_alt');
            const authorInput = document.getElementById('user_id');
            const outTitle = box.querySelector('[data-article-preview-title]');
            const outTitleLen = box.querySelector('[data-article-preview-title-len]');
            const outTitlePx = box.querySelector('[data-article-preview-title-px]');
            const outDesc = box.querySelector('[data-article-preview-desc]');
            const outDescLen = box.querySelector('[data-article-preview-desc-len]');
            const outDescPx = box.querySelector('[data-article-preview-desc-px]');
            const outTitleBar = box.querySelector('[data-article-preview-title-bar]');
            const outDescBar = box.querySelector('[data-article-preview-desc-bar]');
            const outCtr = box.querySelector('[data-article-preview-ctr]');
            const outReadinessStatus = box.querySelector('[data-article-readiness-status]');
            const outReadinessScore = box.querySelector('[data-article-readiness-score]');
            const outReadinessItems = box.querySelector('[data-article-readiness-items]');
            const outToneRisk = box.querySelector('[data-article-tone-risk]');
            const outToneScore = box.querySelector('[data-article-tone-score]');
            const outToneReasons = box.querySelector('[data-article-tone-reasons]');
            const outToneSuggestions = box.querySelector('[data-article-tone-suggestions]');

            const labels = {
                title: box.dataset.lblTitle || 'Meta title length',
                description: box.dataset.lblDescription || 'Meta description length',
                titlePx: box.dataset.lblTitlePx || 'Meta title width',
                descriptionPx: box.dataset.lblDescriptionPx || 'Meta description width',
                good: box.dataset.lblGood || 'good',
                short: box.dataset.lblShort || 'too short',
                long: box.dataset.lblLong || 'too long',
                ctrLabel: box.dataset.lblCtrLabel || 'CTR suggestion',
                ctrOk: box.dataset.lblCtrOk || 'Looks good.',
                ctrTitleSpecific: box.dataset.lblCtrTitleSpecific || 'Title feels broad - add a specific term.',
                ctrTitleNoNumber: box.dataset.lblCtrTitleNoNumber || 'Consider adding a concrete number or metric.',
                ctrDescValue: box.dataset.lblCtrDescValue || 'Description should state user value more clearly.',
                ctrNews: box.dataset.lblCtrNews || 'News: make the update angle clear.',
                ctrAnalysis: box.dataset.lblCtrAnalysis || 'Analysis: signal insight, comparison, or interpretation.',
                ctrGuide: box.dataset.lblCtrGuide || 'Guide: use step-oriented, practical wording.',
                ctrReview: box.dataset.lblCtrReview || 'Review: mention evaluation scope (pros/cons, performance).',
                readinessReady: box.dataset.lblReadinessReady || 'ready',
                readinessWarning: box.dataset.lblReadinessWarning || 'warning',
                readinessCritical: box.dataset.lblReadinessCritical || 'critical',
                checkTitle: box.dataset.lblReadinessCheckTitle || 'Title length',
                checkDescription: box.dataset.lblReadinessCheckDescription || 'Description length',
                checkNoindex: box.dataset.lblReadinessCheckNoindex || 'Noindex disabled',
                checkCanonical: box.dataset.lblReadinessCheckCanonical || 'Canonical URL',
                checkOg: box.dataset.lblReadinessCheckOg || 'OG title/description',
                checkImage: box.dataset.lblReadinessCheckImage || 'Featured image width',
                checkAlt: box.dataset.lblReadinessCheckAlt || 'Featured image alt',
                checkAuthor: box.dataset.lblReadinessCheckAuthor || 'Author profile completeness',
                helpTitle: box.dataset.lblReadinessHelpTitle || 'Google News snippets perform better with clear title length.',
                helpDescription: box.dataset.lblReadinessHelpDescription || 'A well-sized description improves crawl understanding and CTR.',
                helpNoindex: box.dataset.lblReadinessHelpNoindex || 'Noindex prevents article visibility in search and Google News.',
                helpCanonical: box.dataset.lblReadinessHelpCanonical || 'Canonical helps consolidate duplicate URLs under one preferred URL.',
                helpOg: box.dataset.lblReadinessHelpOg || 'Complete OG fields improve social and discovery previews.',
                helpImage: box.dataset.lblReadinessHelpImage || 'Google News strongly prefers high-resolution lead images (1200px+).',
                helpAlt: box.dataset.lblReadinessHelpAlt || 'Alt text improves accessibility and image context understanding.',
                helpAuthor: box.dataset.lblReadinessHelpAuthor || 'Complete author identity supports trust and E-E-A-T signals.',
                toneTitle: box.dataset.lblToneTitle || 'Editorial tone guardrail',
                toneLow: box.dataset.lblToneLow || 'low',
                toneMedium: box.dataset.lblToneMedium || 'medium',
                toneHigh: box.dataset.lblToneHigh || 'high',
                toneReasonGeneric: box.dataset.lblToneReasonGeneric || 'Too generic wording detected.',
                toneReasonMarketing: box.dataset.lblToneReasonMarketing || 'Marketing-heavy language detected.',
                toneReasonStuffing: box.dataset.lblToneReasonStuffing || 'Keyword stuffing pattern detected.',
                toneReasonTitle: box.dataset.lblToneReasonTitle || 'Title may be too broad or vague.',
                toneReasonDesc: box.dataset.lblToneReasonDesc || 'Description is too short for context.',
                toneReasonInfo: box.dataset.lblToneReasonInfo || 'Description lacks concrete information signals.',
                toneSuggestionSpecific: box.dataset.lblToneSuggestionSpecific || 'Add concrete entities: model name, version, date, company.',
                toneSuggestionValue: box.dataset.lblToneSuggestionValue || 'Add one sentence explaining user impact and why now.',
                toneSuggestionMarketing: box.dataset.lblToneSuggestionMarketing || 'Remove CTA-like phrases and keep neutral newsroom tone.'
            };

            const textWidth = textWidthFactory();
            let pendingImageWidth = Number(box.dataset.initFeaturedImageWidth || 0);
            const initialHasImage = Number(box.dataset.initHasFeaturedImage || 0) === 1;
            const toneMediumThreshold = Number(box.dataset.toneMediumThreshold || 35);
            const toneHighThreshold = Math.max(Number(box.dataset.toneHighThreshold || 60), toneMediumThreshold + 1);
            let pinnedPopover = null;

            const statusLabel = function (status) {
                return status === 'pass' ? labels.readinessReady : (status === 'warn' ? labels.readinessWarning : labels.readinessCritical);
            };

            const statusClass = function (status) {
                return status === 'pass'
                    ? 'bg-emerald-100 text-emerald-700'
                    : (status === 'warn' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700');
            };

            const checkStatusClass = function (status) {
                return status === 'pass' ? 'text-emerald-700' : (status === 'warn' ? 'text-amber-700' : 'text-rose-700');
            };

            const readImageWidth = function () {
                return new Promise(function (resolve) {
                    if (!featuredImageInput || !featuredImageInput.files || featuredImageInput.files.length === 0) {
                        resolve(pendingImageWidth);
                        return;
                    }
                    const file = featuredImageInput.files[0];
                    const tmp = URL.createObjectURL(file);
                    const img = new Image();
                    img.onload = function () {
                        const w = Number(img.naturalWidth || 0);
                        URL.revokeObjectURL(tmp);
                        resolve(w);
                    };
                    img.onerror = function () {
                        URL.revokeObjectURL(tmp);
                        resolve(0);
                    };
                    img.src = tmp;
                });
            };

            const analyzeTone = function (title, description) {
                const text = (title + ' ' + description).toLowerCase();
                const generic = ['ultimate guide', 'everything you need to know', 'top 10', 'best ever', 'game changer', 'next big thing'];
                const cta = ['click now', 'do not miss', 'limited time', 'must read now'];
                let score = 0;
                const reasons = [];
                const suggestions = [];

                if (generic.some(function (p) { return text.indexOf(p) >= 0; })) {
                    score += 20;
                    reasons.push('generic');
                }
                if (cta.some(function (p) { return text.indexOf(p) >= 0; })) {
                    score += 25;
                    reasons.push('marketing');
                }

                const tokens = text.split(/\s+/).filter(function (t) { return t.length > 2; });
                if (tokens.length > 0) {
                    const freq = {};
                    tokens.forEach(function (t) {
                        freq[t] = (freq[t] || 0) + 1;
                    });
                    let top = 0;
                    Object.keys(freq).forEach(function (k) { if (freq[k] > top) top = freq[k]; });
                    if ((top / tokens.length) >= 0.18) {
                        score += 20;
                        reasons.push('stuffing');
                    }
                }

                if (!/\d/.test(title) && title.length < 38) {
                    score += 10;
                    reasons.push('title');
                }
                if (description.length > 0 && description.length < 95) {
                    score += 10;
                    reasons.push('desc');
                }
                if (!/\b(what|how|why|when|impact|update|version|model|release|benchmark|price|roadmap)\b/i.test(description)) {
                    score += 10;
                    reasons.push('info');
                }

                if (score >= 40) {
                    suggestions.push('specific');
                    suggestions.push('value');
                }
                if (score >= 60) suggestions.push('marketing');

                const risk = score >= toneHighThreshold ? 'high' : (score >= toneMediumThreshold ? 'medium' : 'low');
                return { risk: risk, score: score, reasons: reasons, suggestions: suggestions };
            };

            const renderTone = function (analysis) {
                if (!outToneRisk || !outToneScore || !outToneReasons || !outToneSuggestions) return;
                const riskLabel = analysis.risk === 'high' ? labels.toneHigh : (analysis.risk === 'medium' ? labels.toneMedium : labels.toneLow);
                const riskClass = analysis.risk === 'high'
                    ? 'bg-rose-100 text-rose-700'
                    : (analysis.risk === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');
                const reasonMap = {
                    generic: labels.toneReasonGeneric,
                    marketing: labels.toneReasonMarketing,
                    stuffing: labels.toneReasonStuffing,
                    title: labels.toneReasonTitle,
                    desc: labels.toneReasonDesc,
                    info: labels.toneReasonInfo
                };
                const suggestionMap = {
                    specific: labels.toneSuggestionSpecific,
                    value: labels.toneSuggestionValue,
                    marketing: labels.toneSuggestionMarketing
                };

                outToneRisk.textContent = riskLabel;
                outToneRisk.className = 'rounded-full px-2 py-0.5 text-[11px] font-semibold ' + riskClass;
                outToneScore.textContent = analysis.score + '%';
                outToneReasons.innerHTML = (analysis.reasons.length ? analysis.reasons : ['info']).map(function (key) {
                    return '<li>' + escapeHtml(reasonMap[key] || labels.toneReasonInfo) + '</li>';
                }).join('');
                outToneSuggestions.innerHTML = (analysis.suggestions.length ? analysis.suggestions : ['specific']).map(function (key) {
                    return '<li>' + escapeHtml(suggestionMap[key] || labels.toneSuggestionSpecific) + '</li>';
                }).join('');
            };

            const escapeHtml = function (str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

            const renderReadiness = function (ctx) {
                if (!outReadinessStatus || !outReadinessScore || !outReadinessItems) return;
                const checks = [];
                const pushCheck = function (label, status, targetId, helpText) {
                    checks.push({ label: label, status: status, targetId: targetId || null, helpText: helpText || '' });
                };
                pushCheck(labels.checkTitle, (ctx.titleLen >= 35 && ctx.titleLen <= 70) ? 'pass' : (ctx.titleLen >= 30 && ctx.titleLen <= 80 ? 'warn' : 'fail'), 'meta_title', labels.helpTitle);
                pushCheck(labels.checkDescription, (ctx.descLen >= 110 && ctx.descLen <= 180) ? 'pass' : (ctx.descLen >= 90 && ctx.descLen <= 220 ? 'warn' : 'fail'), 'meta_description', labels.helpDescription);
                pushCheck(labels.checkNoindex, ctx.noindex ? 'fail' : 'pass', (noindexInput && noindexInput.id) ? noindexInput.id : null, labels.helpNoindex);
                pushCheck(labels.checkCanonical, ctx.canonicalOk ? 'pass' : 'warn', 'canonical_url', labels.helpCanonical);
                pushCheck(labels.checkOg, ctx.ogOk ? 'pass' : 'warn', 'og_title', labels.helpOg);
                pushCheck(labels.checkImage, ctx.imageOk ? 'pass' : (ctx.hasImage ? 'warn' : 'fail'), 'featured_image', labels.helpImage);
                pushCheck(labels.checkAlt, ctx.altOk ? 'pass' : 'warn', 'featured_image_alt', labels.helpAlt);
                pushCheck(labels.checkAuthor, ctx.authorOk ? 'pass' : 'warn', 'user_id', labels.helpAuthor);

                let points = 0;
                checks.forEach(function (check) {
                    points += check.status === 'pass' ? 1 : (check.status === 'warn' ? 0.5 : 0);
                });
                const score = Math.round((points / Math.max(1, checks.length)) * 100);
                const overall = score >= 85 ? 'pass' : (score >= 60 ? 'warn' : 'fail');

                outReadinessStatus.textContent = statusLabel(overall);
                outReadinessStatus.className = 'rounded-full px-2 py-0.5 text-[11px] font-semibold ' + statusClass(overall);
                outReadinessScore.textContent = score + '%';

                outReadinessItems.innerHTML = checks.map(function (check) {
                    const targetAttr = check.targetId ? (' data-focus-target="' + check.targetId + '"') : '';
                    const tooltip = escapeHtml(check.helpText || '');
                    return '<li><button type="button" class="flex w-full items-center justify-between gap-2 rounded px-1 py-0.5 text-left hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"' + targetAttr + '><span class="inline-flex items-center gap-1">' + escapeHtml(check.label) + '<span class="relative inline-flex" data-popover-wrap><span tabindex="0" role="button" class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-gray-300 text-[10px] text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500" data-popover-trigger aria-label="' + tooltip + '">i</span><span class="pointer-events-none absolute left-1/2 top-full z-20 mt-1 hidden w-56 -translate-x-1/2 rounded-md border border-gray-200 bg-white p-2 text-[11px] font-normal leading-relaxed text-gray-700 shadow-lg" data-popover-bubble>' + tooltip + '</span></span></span><span class="font-semibold ' + checkStatusClass(check.status) + '">' + statusLabel(check.status) + '</span></button></li>';
                }).join('');
            };
            const render = function () {
                const fallbackTitle = (fallbackTitleInput && fallbackTitleInput.value ? fallbackTitleInput.value : '').trim();
                const title = (titleInput && titleInput.value ? titleInput.value : '').trim() || fallbackTitle || '...';
                const desc = (descInput && descInput.value ? descInput.value : '').trim() || '...';
                const titleLen = title.length;
                const descLen = desc.length;
                const titlePx = textWidth(title, '400 20px Arial');
                const descPx = textWidth(desc, '400 13px Arial');
                const titleState = state(titleLen, 30, 60, labels);
                const descState = state(descLen, 70, 160, labels);
                const titlePxState = state(titlePx, 300, 580, labels);
                const descPxState = state(descPx, 400, 920, labels);

                if (outTitle) outTitle.textContent = title;
                if (outDesc) outDesc.textContent = desc;
                if (outTitleLen) {
                    outTitleLen.textContent = labels.title + ': ' + titleLen + ' (' + titleState + ')';
                    outTitleLen.className = 'mt-1 text-[11px] font-medium ' + stateClass(titleState, labels);
                }
                if (outDescLen) {
                    outDescLen.textContent = labels.description + ': ' + descLen + ' (' + descState + ')';
                    outDescLen.className = 'mt-1 text-[11px] font-medium ' + stateClass(descState, labels);
                }
                if (outTitlePx) {
                    outTitlePx.textContent = labels.titlePx + ': ' + titlePx + 'px (' + titlePxState + ')';
                    outTitlePx.className = 'mt-1 text-[11px] font-medium ' + stateClass(titlePxState, labels);
                }
                if (outDescPx) {
                    outDescPx.textContent = labels.descriptionPx + ': ' + descPx + 'px (' + descPxState + ')';
                    outDescPx.className = 'mt-1 text-[11px] font-medium ' + stateClass(descPxState, labels);
                }
                if (outTitleBar) {
                    outTitleBar.style.width = Math.min(Math.round((titleLen / 80) * 100), 100) + '%';
                    outTitleBar.className = 'h-1.5 rounded transition-all ' + (titleState === labels.good ? 'bg-emerald-500' : (titleState === labels.long ? 'bg-amber-500' : 'bg-rose-500'));
                }
                if (outDescBar) {
                    outDescBar.style.width = Math.min(Math.round((descLen / 220) * 100), 100) + '%';
                    outDescBar.className = 'h-1.5 rounded transition-all ' + (descState === labels.good ? 'bg-emerald-500' : (descState === labels.long ? 'bg-amber-500' : 'bg-rose-500'));
                }

                if (outCtr) {
                    const hints = [];
                    if (titleLen < 40) hints.push(labels.ctrTitleSpecific);
                    if (!/\d/.test(title)) hints.push(labels.ctrTitleNoNumber);
                    if (descLen < 100) hints.push(labels.ctrDescValue);
                    const ct = (typeInput && typeInput.value ? typeInput.value : 'news');
                    const typeHint = ct === 'analysis'
                        ? labels.ctrAnalysis
                        : (ct === 'guide' ? labels.ctrGuide : (ct === 'review' ? labels.ctrReview : labels.ctrNews));
                    const message = hints.length ? hints[0] : typeHint;
                    outCtr.textContent = labels.ctrLabel + ': ' + message;
                    outCtr.className = 'mt-2 text-xs ' + (hints.length ? 'text-amber-700' : 'text-emerald-700');
                }
                renderTone(analyzeTone(title, desc));

                const canonicalValue = (canonicalInput && canonicalInput.value ? canonicalInput.value : '').trim();
                const canonicalOk = canonicalValue === '' || /^https?:\/\//i.test(canonicalValue);
                const ogTitleValue = (ogTitleInput && ogTitleInput.value ? ogTitleInput.value : '').trim();
                const ogDescValue = (ogDescInput && ogDescInput.value ? ogDescInput.value : '').trim();
                const ogOk = ogTitleValue.length >= 20 && ogDescValue.length >= 60;
                const noindex = !!(noindexInput && noindexInput.checked);
                const altOk = !!(featuredAltInput && (featuredAltInput.value || '').trim().length >= 5);
                const hasImage = initialHasImage || (!!(featuredImageInput && featuredImageInput.files && featuredImageInput.files.length > 0));
                const selectedAuthor = authorInput && authorInput.selectedOptions && authorInput.selectedOptions[0]
                    ? authorInput.selectedOptions[0]
                    : null;
                const authorOk = !!(selectedAuthor
                    && selectedAuthor.value
                    && selectedAuthor.dataset.hasSlug === '1'
                    && selectedAuthor.dataset.hasTitle === '1'
                    && Number(selectedAuthor.dataset.bioWords || 0) >= 40);

                readImageWidth().then(function (imageWidth) {
                    pendingImageWidth = Number(imageWidth || 0);
                    const imageOk = !hasImage ? false : pendingImageWidth >= 1200;
                    renderReadiness({
                        titleLen: titleLen,
                        descLen: descLen,
                        noindex: noindex,
                        canonicalOk: canonicalOk,
                        ogOk: ogOk,
                        imageOk: imageOk,
                        hasImage: hasImage,
                        altOk: altOk,
                        authorOk: authorOk
                    });
                });
            };

            [titleInput, fallbackTitleInput, descInput, typeInput, canonicalInput, ogTitleInput, ogDescInput, noindexInput, featuredImageInput, featuredAltInput, authorInput].filter(Boolean).forEach(function (el) {
                el.addEventListener('input', render);
                el.addEventListener('change', render);
            });

            if (outReadinessItems) {
                outReadinessItems.addEventListener('click', function (event) {
                    const popTrigger = event.target && event.target.closest ? event.target.closest('[data-popover-trigger]') : null;
                    if (popTrigger) {
                        event.preventDefault();
                        event.stopPropagation();
                        const wrap = popTrigger.closest('[data-popover-wrap]');
                        if (!wrap) return;
                        const bubble = wrap.querySelector('[data-popover-bubble]');
                        if (!bubble) return;
                        const isPinnedSame = pinnedPopover === bubble;
                        outReadinessItems.querySelectorAll('[data-popover-bubble]').forEach(function (el) { el.classList.add('hidden'); });
                        outReadinessItems.querySelectorAll('[data-popover-trigger]').forEach(function (el) {
                            el.classList.remove('bg-indigo-50', 'text-indigo-700', 'border-indigo-300');
                            el.setAttribute('aria-pressed', 'false');
                        });
                        if (isPinnedSame) {
                            pinnedPopover = null;
                        } else {
                            bubble.classList.remove('hidden');
                            popTrigger.classList.add('bg-indigo-50', 'text-indigo-700', 'border-indigo-300');
                            popTrigger.setAttribute('aria-pressed', 'true');
                            pinnedPopover = bubble;
                        }
                        return;
                    }
                    const btn = event.target && event.target.closest ? event.target.closest('[data-focus-target]') : null;
                    if (!btn) return;
                    const targetId = btn.getAttribute('data-focus-target');
                    if (!targetId) return;
                    const target = document.getElementById(targetId);
                    if (!target) return;
                    var tabMap = {
                        featured_image: 'image',
                        featured_image_alt: 'image',
                        title: 'content',
                        slug: 'content',
                        excerpt: 'content',
                        body: 'content',
                        meta_title: 'seo',
                        meta_description: 'seo',
                        og_title: 'seo',
                        og_description: 'seo',
                        canonical_url: 'seo',
                        user_id: 'publishing'
                    };
                    if (typeof window.setAdminArticleTab === 'function' && tabMap[targetId]) {
                        window.setAdminArticleTab(tabMap[targetId]);
                    }
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(function () {
                        if (typeof target.focus === 'function') target.focus({ preventScroll: true });
                    }, 250);
                });

                outReadinessItems.addEventListener('mouseover', function (event) {
                    const trigger = event.target && event.target.closest ? event.target.closest('[data-popover-trigger]') : null;
                    if (!trigger) return;
                    const wrap = trigger.closest('[data-popover-wrap]');
                    const bubble = wrap ? wrap.querySelector('[data-popover-bubble]') : null;
                    if (pinnedPopover && pinnedPopover !== bubble) return;
                    if (bubble) bubble.classList.remove('hidden');
                });

                outReadinessItems.addEventListener('mouseout', function (event) {
                    const trigger = event.target && event.target.closest ? event.target.closest('[data-popover-trigger]') : null;
                    if (!trigger) return;
                    const wrap = trigger.closest('[data-popover-wrap]');
                    const bubble = wrap ? wrap.querySelector('[data-popover-bubble]') : null;
                    if (pinnedPopover && pinnedPopover === bubble) return;
                    if (bubble) bubble.classList.add('hidden');
                });

                document.addEventListener('click', function (event) {
                    if (!outReadinessItems.contains(event.target)) {
                        outReadinessItems.querySelectorAll('[data-popover-bubble]').forEach(function (el) {
                            el.classList.add('hidden');
                        });
                        outReadinessItems.querySelectorAll('[data-popover-trigger]').forEach(function (el) {
                            el.classList.remove('bg-indigo-50', 'text-indigo-700', 'border-indigo-300');
                            el.setAttribute('aria-pressed', 'false');
                        });
                        pinnedPopover = null;
                    }
                });
            }
            render();
        };
    })();
</script>
