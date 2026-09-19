<?php

namespace App\Services;

use App\Models\AiArticleGeneration;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Setting;
use App\Support\ArticleSources;
use App\Support\ReservedPathSegments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

class ClaudeArticleGeneratorService
{
    private const MODEL_DEFAULT = 'claude-sonnet-4-6';

    // Cost per 1M tokens (USD) — Claude Sonnet 4.6
    private const COST_INPUT_PER_M = 3.0;
    private const COST_OUTPUT_PER_M = 15.0;

    private readonly ImageSearchService $imageService;

    public function __construct(ImageSearchService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Process a pending AiArticleGeneration record:
     * call Claude, parse response, create Article.
     */
    /**
     * Called from GenerateArticleJob after it has atomically claimed the record
     * (status already set to processing via a single DB::update WHERE pending).
     * Skips the redundant status update to avoid race conditions.
     */
    public function processAlreadyClaimed(AiArticleGeneration $gen): void
    {
        $model = Setting::get('bot_model', config('novaranews.bot_model', self::MODEL_DEFAULT));
        $gen->update(['model' => $model]);

        $this->generate($gen);
    }

    /**
     * Full process including status flip — used for sync/manual calls (artisan command).
     */
    public function process(AiArticleGeneration $gen): void
    {
        $model = Setting::get('bot_model', config('novaranews.bot_model', self::MODEL_DEFAULT));
        $gen->update(['status' => AiArticleGeneration::STATUS_PROCESSING, 'model' => $model]);

        $this->generate($gen);
    }

    private function generate(AiArticleGeneration $gen): void
    {

        try {
            $categoryKey = $gen->source_packets['category_key'] ?? null;
            $category = $this->resolveCategory($categoryKey);

            if (! $category) {
                throw new \RuntimeException("Cannot resolve category for key: {$categoryKey}");
            }

            $locales = $gen->target_locales ?? config('novaranews.locales', ['en']);

            // Pick the primary locale (source locale or first in list)
            $primaryLocale = $gen->source_locale ?? $locales[0] ?? 'en';

            [$systemPrompt, $userPrompt] = $this->buildPrompt($gen, $primaryLocale, $category->key);

            $apiResponse = $this->callClaude($userPrompt, $systemPrompt);

            $inputTokens = $apiResponse['usage']['input_tokens'] ?? 0;
            $outputTokens = $apiResponse['usage']['output_tokens'] ?? 0;
            $rawText = $apiResponse['content'][0]['text'] ?? '';

            $parsed = $this->parseResponse($rawText);

            // Handle nested format from template prompt (translation key)
            // Template asks for {"translation": {title, slug, body...}} but generate() expects flat keys.
            if (isset($parsed['translation']) && is_array($parsed['translation'])) {
                $parsed = array_merge($parsed, $parsed['translation']);
                unset($parsed['translation']);
            }

            // Use Claude's own English keyword suggestion for image search (better Unsplash results)
            // Fallback to article title if not provided
            $imageQuery = trim($parsed['image_keywords'] ?? '') ?: $parsed['title'];
            $imageData  = $this->imageService->search($imageQuery, $primaryLocale);

            // Build article
            $slug = $this->uniqueSlug($parsed['slug'] ?? Str::slug($parsed['title']), $primaryLocale);

            $canonicalUrl = route('article.show', [
                'locale'        => $primaryLocale,
                'articlePrefix' => article_path_segment($primaryLocale),
                'articleSlug'   => $slug,
            ]);

            /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
            $publicDisk = Storage::disk('public');
            $ogDiskPath = $imageData['path']
                ? $publicDisk->url($imageData['path'])
                : null;
            $ogImageUrl = $imageData['path']
                ? (preg_match('/^https?:\/\//i', $ogDiskPath ?? '') === 1 ? $ogDiskPath : url((string) $ogDiskPath))
                : null;

            $botUserId = \App\Models\User::query()->where('is_admin', true)->value('id');
            $sourceEntries = $this->sourcesForGeneration($gen);

            $article = DB::transaction(function () use ($gen, $category, $primaryLocale, $parsed, $slug, $imageData, $botUserId, $canonicalUrl, $ogImageUrl, $sourceEntries) {
                $autoPublish = Setting::get('bot_auto_publish', false);
                $status      = $autoPublish ? 'published' : 'ai_ready';
                $publishedAt = $autoPublish ? now() : null;

                $article = Article::query()->create([
                    'category_id'            => $category->id,
                    'user_id'                => $botUserId,
                    'locale'                 => $primaryLocale,
                    'status'                 => $status,
                    'published_at'           => $publishedAt,
                    'featured_image'         => $imageData['path'] ?? null,
                    'featured_image_alt'     => $parsed['title'],
                    'featured_image_caption' => null,
                    'is_breaking'            => false,
                    'content_type'           => 'news',
                    'is_ai_generated'        => true,
                ]);

                ArticleTranslation::query()->create([
                    'article_id'       => $article->id,
                    'locale'           => $primaryLocale,
                    'title'            => mb_substr($parsed['title'], 0, 255),
                    'slug'             => $slug,
                    'excerpt'          => mb_substr($parsed['excerpt'] ?? '', 0, 2000) ?: null,
                    'body'             => Purify::clean($parsed['body'] ?? ''),
                    'meta_title'       => mb_substr($parsed['meta_title'] ?? '', 0, 255) ?: null,
                    'meta_description' => mb_substr($parsed['meta_description'] ?? '', 0, 500) ?: null,
                    'og_title'         => mb_substr($parsed['meta_title'] ?? '', 0, 255) ?: null,
                    'og_description'   => mb_substr($parsed['meta_description'] ?? '', 0, 500) ?: null,
                    'og_image_url'     => $ogImageUrl,
                    'canonical_url'    => $canonicalUrl,
                    'sources'          => $sourceEntries,
                ]);

                return $article;
            });

            $gen->update([
                'article_id' => $article->id,
                'category_id' => $category->id,
                'status' => AiArticleGeneration::STATUS_DONE,
                'raw_response' => mb_substr($rawText, 0, 65535),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'estimated_cost_usd' => $this->estimateCost($inputTokens, $outputTokens),
                'image_url' => $imageData['original_url'] ?? null,
                'image_alt' => $imageData['alt'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error("ClaudeGenerator: failed gen #{$gen->id}: {$e->getMessage()}");
            $gen->update([
                'status' => AiArticleGeneration::STATUS_FAILED,
                'error_message' => mb_substr($e->getMessage(), 0, 5000),
            ]);
        }
    }

    /**
     * Returns [systemPrompt, userPrompt]
     * Prompt logic adapted from claude-prompt-news.txt
     *
     * @return array{0: string, 1: string}
     */
    private function buildPrompt(AiArticleGeneration $gen, string $locale, string $categoryKey): array
    {
        $langNames = [
            'en' => 'English',
            'tr' => 'Turkish',
            'de' => 'German',
            'fr' => 'French',
            'es' => 'Spanish',
        ];

        $langName = $langNames[$locale] ?? $locale;
        $today    = now()->format('F j, Y');

        $categoryTones = [
            'artificial-intelligence' => 'Lead with real-world impact, not technical jargon. What does this AI development mean for people, industries, or power? Name the model, company, or research lab. Cover capabilities, limitations, and ethical stakes. Avoid hype — explain what actually changed.',
            'technology'              => 'Explain the "so what" for non-experts first. Then technical depth. Cover ethical, social, and competitive implications. Name specific products, companies, and version numbers where confirmed.',
            'mobile'                  => 'Smartphones, tablets, apps, and mobile OS. Lead with what changed for users — new feature, price, release date, OS update. Specs only from confirmed sources. Cover platform (iOS/Android), availability by region, and competitive positioning against current devices.',
            'game'                    => 'Games, engines, platforms, and releases. Lead with title, developer, platform, and release window. Cover what is new mechanically or technically — engine, graphics tech, multiplayer, DLC. Explain what makes this release significant in the current market. No hype; let the features speak.',
            'software'                => 'Developers and technical readers first, but keep it accessible. Name the language, framework, platform, or tool. Explain what changed in this version or release and its downstream impact. Link changes to real developer pain points.',
            'hardware'                => 'PC components, processors, GPUs, RAM, storage, data-center chips, peripherals. Lead with the chip or component name, specs confirmed by the source, and pricing if announced. Cover performance uplift vs prior generation, power envelope, and platform compatibility. Architecture and process node where confirmed.',
        ];

        $categoryTone = $categoryTones[$categoryKey] ?? 'Factual, clear, well-sourced.';

        // Evaluate content richness (mirrors the 3-tier logic from the prompt file)
        $sourceContent = trim((string) ($gen->source_text ?? ''));
        $sourceUrl = trim((string) ($gen->source_url ?? ''));
        $sourceUrlLabel = $sourceUrl !== '' ? $sourceUrl : '(not provided)';
        $urlModeInstruction = $sourceUrl !== ''
            ? 'URL MODE ACTIVE: SOURCE_URL is present. Treat URL facts as primary and allow hard claims only from URL content or official sources explicitly cited there.'
            : 'SOURCE_URL MISSING: do not generate a full article. Return strict JSON with an uncertain short body explaining source verification is insufficient.';
        $factCount = max(
            substr_count($sourceContent, '.'),
            (int) ceil(str_word_count($sourceContent) / 20)
        );

        if ($factCount >= 5 || str_word_count($sourceContent) >= 80) {
            $tierInstruction = 'TIER A — Full content available. Proceed normally to article generation.';
        } elseif ($factCount >= 3 || str_word_count($sourceContent) >= 30) {
            $tierInstruction = 'TIER B — Partial content available. Use only confirmed facts. Expand with permitted context only. Target 600+ words where possible using legitimate editorial depth. Do not fabricate to hit length.';
        } else {
            $tierInstruction = 'TIER B — Minimal source content. Use the confirmed facts from the source and add editorial context (maximum 15% of body) to frame them fully. Reach the 450-word minimum by contextualising — not by padding. A complete article is always required, even with thin source material.';
        }

        $templateUserPrompt = $this->buildPromptFromDocsTemplate(
            $locale,
            $categoryKey,
            $sourceUrl,
            $gen->source_title ?? '',
            $sourceContent,
            $today
        );
        if ($templateUserPrompt !== null) {
            $system = <<<SYSTEM
You are a veteran journalist and editor-in-chief with 20 years of newsroom experience
across major international publications in five languages: English, Turkish, German,
French, and Spanish. You have worked in Istanbul, Berlin, Paris, Madrid, and London.

Today's date: {$today}
Article language: {$langName} — write natively in this language, not as a translation from English.
Category: {$categoryKey}

You write like a human editor, not like an AI assistant. Your output must be
indistinguishable from professional journalism written by a native speaker of {$langName}.
You never produce AI filler phrases, mechanical structure, or synthetic summaries.
Every sentence must earn its place in the article.

The minimum article body length is 450 words. There are no exceptions.
Follow all instructions in the user prompt precisely and completely.
SYSTEM;

            return [$system, $templateUserPrompt];
        }

        $languageVoice = match ($locale) {
            'tr' => <<<LANG
TURKISH VOICE GUIDE
Write as a Turkish journalist for a Turkish newsroom — not as a translation from English.

Natural connectors (use sparingly, vary them): "öte yandan", "buna karşın", "ne var ki", "bu bağlamda", "öte yandan", "bu süreçte"
Permitted hedges (max 2 total): "edinilen bilgilere göre", "öğrenildiğine göre"
Sentence endings: vary between -dı/-di past tense, -yor present, and noun-phrase endings. Never end 3 consecutive sentences with the same tense.
Do NOT start more than 2 consecutive sentences with "Bu" or "Söz konusu".
Avoid literal translations of English constructions. "Following this development" → rewrite naturally in Turkish, not "bu gelişmenin ardından" every time.
Subheadings must sound like a Turkish newspaper section header, not a translated English heading.
LANG,
            'de' => <<<LANG
GERMAN VOICE GUIDE
Write in the style of a German quality newspaper (Zeit, Spiegel) — precise, structured, no tabloid tone.

Use compound nouns where natural (do not force them).
Vary sentence structure: mix Hauptsatz with Nebensatz constructions. Avoid 3+ consecutive simple main clauses.
Reported speech: use Konjunktiv I naturally ("er sagte, das sei...", "wie es heißt").
Connectors to vary: "Allerdings", "Dennoch", "Darüber hinaus", "Gleichwohl", "Indes" — use each at most once.
Subheadings: concise noun phrases, not full sentences.
LANG,
            'fr' => <<<LANG
FRENCH VOICE GUIDE
Write in the style of Le Monde or Le Figaro — clear, precise, with measured authority.

Use "en effet", "pourtant", "néanmoins", "par ailleurs", "or" as connectors — vary them, never repeat the same one within 3 paragraphs.
Alternate active and passive voice. French journalism uses passive more than English.
Use subjonctif naturally for reported speech and concessive clauses.
Avoid anglicisms unless quoting.
Subheadings: short, declarative, typically a noun phrase.
LANG,
            'es' => <<<LANG
SPANISH VOICE GUIDE
Write in the style of El País or El Mundo — direct, authoritative, clear.

Natural connectors: "sin embargo", "no obstante", "por su parte", "en ese sentido", "así las cosas" — vary them.
Use inverted constructions occasionally (verb before subject) for rhythm variation.
Use subjunctive naturally for reported speech ("señaló que fuera necesario").
Avoid Latinoamericanismos if the topic is clearly Spain-focused, and vice versa. When neutral, use universal Spanish.
Subheadings: short noun phrases or brief declarative sentences.
LANG,
            default => <<<LANG
ENGLISH VOICE GUIDE
Write in the style of The Guardian or AP — direct, active, informative.

Vary paragraph openers: sometimes a time reference, sometimes a person, sometimes a statistic, sometimes a place. Never the same opener type twice in a row.
Use "Meanwhile", "Yet", "Still", "Even so" as connectors — sparingly, one per article maximum each.
Prefer active voice. Passive only when the subject is unknown or less important.
Subheadings: verb-led or noun-phrase, under 6 words.
LANG,
        };

        $system = <<<SYSTEM
You are a senior journalist and editor-in-chief with 15+ years of newsroom experience at major international publications.

You BOTH:
1) Write the article
2) Critically review and fix it before output

Today's date: {$today}
Article language: {$langName}
Category: {$categoryKey}
Category tone guidance: {$categoryTone}

Your output must be indistinguishable from professional human journalism and ready for direct publication.

You are not an AI assistant. You are a newsroom journalist writing for publication.

Write:
- Direct and confident
- Naturally varied in rhythm
- With human texture — uneven, alive, editorially distinct

Avoid:
- AI tone and filler phrases
- Academic or overly polished language
- Generic transitions
- Mechanical perfection — real journalists leave editorial fingerprints

You never add meta-commentary, disclaimers, or phrases like "As an AI". You write the article — nothing else.
SYSTEM;

        // ── Mode-specific sections ────────────────────────────────────────────────
            $missionText = <<<'TXT'
MISSION

Transform this source into a fully original, publication-ready news article. Target 600+ words when source depth allows (450+ accepted for thin-source stories).

Internal process (do not output):
1. Evaluate source content tier and follow the correct path
2. Extract all confirmed facts, specs, and data points
3. Identify core news event and strongest editorial angle
4. Select primary keyword using the 3-step extraction below
5. Draft the full article
6. Run QA Pass 1
7. Fix all failing items
8. Expand to 600+ words if needed
9. Run QA Pass 2 after any expansion
10. Output final JSON only
TXT;
            $modeSection = <<<'TXT'
NEWS MODE (STRICT)

This is a news article. Not a guide. Not a blog. Not a review.

Write only:
- What happened
- Why now
- What changed
- Who is affected

Never:
- Give advice or recommendations
- Compare products subjectively
- Use bullet or list structure in the body
- Address the reader directly
TXT;
            $leadSection = <<<TXT
LEAD PARAGRAPH (MANDATORY ENFORCEMENT)

The first paragraph must:
- Open with a time reference (this month, recently, in late 2025, etc. — in {$langName})
- State clearly what happened
- Signal why it matters
- Clearly answer who / what / when

ENFORCEMENT:
After drafting the lead, apply this test:
"If someone reads only this paragraph, do they know what happened and why it matters?"
If NO → the lead has failed. Rewrite before writing any other section.
TXT;
            $structureSection = <<<TXT
ARTICLE STRUCTURE

Use only: <p>, <h2>, <h3>

The structure must serve the story — not a fixed template. Choose the shape that best fits the content:

DEFAULT (3 h2s):
1. Lead paragraph — time anchor + event + impact (no heading)
2. <h2> background/context — timeline, key players, how we got here
3. <h2> key developments — core facts, data, named institutions
4. <h2> analysis/implications — consequences, who is affected
5. Closing paragraph — what comes next (no heading)

ADAPT when needed:
- Tight story or Tier B-minimal source → 2 h2s + strong closing (do not pad)
- Complex multi-angle story → add a 4th h2 using h3 sub-sections
- Breaking news → lead + 1 h2 context block + closing may suffice

All subheadings must be in {$langName} and reflect the actual section content — never generic labels like "Background" or "Analysis".

Paragraph rules:
- 2 to 4 sentences per paragraph
- Vary sentence length deliberately within each paragraph: short. Then longer and more complex. Then medium.
- No paragraph repeats the idea of the previous one
- Every paragraph must add new information or perspective
- No closing summary paragraph ("In conclusion this shows that...")

PARAGRAPH OPENER AUDIT (mandatory before QA Pass 1):
List the first word of every paragraph. If the same word appears as an opener 3+ times, rewrite those openers.
If more than 2 consecutive paragraphs start with the same part of speech (article, pronoun, proper noun), rewrite at least one.
TXT;
            $qaExtras = <<<TXT
✔ Source tier evaluated and correct path followed
✔ Lead paragraph has time anchor, event, and impact — passed enforcement test
✔ No listicle structure, no advice, no subjective comparisons
✔ Paragraph opener audit passed — no repeated opener words or types
TXT;
        $user = <<<USER
{$languageVoice}

---

SOURCE CONTENT EVALUATION:
{$tierInstruction}
{$urlModeInstruction}

SOURCE:
Title: {$gen->source_title}
Source URL: {$sourceUrlLabel}
Content: {$sourceContent}
Category: {$categoryKey}
Language: {$langName}
Today: {$today}

---

{$missionText}

---

{$modeSection}

---

FACTUAL INTEGRITY

TIER 1 — SOURCE FACTS (strict):
Numbers, prices, specs, dates, model names, and specific claims must come directly from SOURCE_URL
or official sources explicitly cited in SOURCE_URL content.
If not confirmed → remove entirely. Do not approximate. Do not guess.

TIER 2 — GENERAL CONTEXT (permitted and bounded):
Widely known industry context may be written without attribution.
Use to add editorial depth — competitive position, market relevance, industry direction, launch significance.

HARD LIMIT: Tier 2 content must not exceed 15% of the total article body.

If uncertain whether something qualifies as general knowledge → use one of these phrases (maximum 2 uses each):
- In Turkish: "sektör gözlemlerine göre", "analist değerlendirmelerine göre", "pazar verilerine göre"
- In other languages: equivalent hedging phrases appropriate to the language

Never invent specific data. Never estimate numbers.

---

ATTRIBUTION RULE

RULE A — Keep sourcing explicit and neutral (source type must be clear where needed).
RULE B — Publication/source names may be used only when factual and non-promotional.
RULE C — Context phrases like "sektör gözlemlerine göre" are permitted as hedges, not fabricated attribution.

---

FILLER BAN (STRICT)

The following sentence types are strictly prohibited anywhere in the article body:

BANNED PATTERNS:
- Any sentence that says "this development is important" without explaining why
- Any sentence that could be removed without losing information
- Any sentence that restates what the previous sentence already said
- Any sentence whose only function is to transition or fill space
- Phrases: "it remains to be seen", "experts say", "many believe", "in conclusion", "as a result"
- In Turkish: "Bu gelişme sektörde önemli bir yer tutuyor", "Konuyla ilgili gelişmeler yakından takip ediliyor"

If a sentence does not add a new fact, a new perspective, or a new dimension to the story → delete it.

---

ORIGINALITY

Do not follow the structure of the source content.
Do not reuse its sentences or phrasing.
Rebuild the story with a fresh editorial angle and entirely original sentence construction.

---

HEADLINE ENGINE

Generate 5 headline candidates internally. Do not output them.

Score each on:
- Clarity of news event
- Search intent match
- Click-through potential
- Presence of impact or consequence
- Absence of clickbait or vague hype

Final headline must:
- Name the subject clearly
- State what happened or changed
- Include the primary keyword naturally
- Signal why this matters to the reader
- Be under 80 characters

Before finalizing: "Would a real reader click this because it tells them something concrete and consequential?"
If no → rewrite until yes.

---

GOOGLE NEWS COMPLIANCE (STRICT)

- Headline must be factual and non-clickbait.
- Lead must clearly answer who/what/when and why it matters.
- No disguised opinion, no hype adjectives without evidence.
- Attribution must be transparent for non-obvious claims.
- If verification is insufficient, reduce certainty instead of guessing.

---

{$leadSection}

---

{$structureSection}

---

WRITING STYLE AND HUMANIZATION

Core voice:
- Natural, confident, direct — never performatively polished
- Each section must feel editorially distinct in tone and rhythm
- Write with human unevenness: some sentences are blunt, some are layered

Rhythm rules:
- Mix sentence lengths within every paragraph: 1 short (under 10 words), 1 medium, 1 longer
- Never write 3 sentences of similar length back to back
- Never repeat the same sentence structure in consecutive sentences (Subject-Verb-Object every time is an AI tell)

Vocabulary rules:
- After drafting, scan each 200-word block: if any content word appears 3+ times, replace all but one with a synonym or restructured phrase
- This includes words in subheadings — do not reuse the same keyword in both a heading and the first sentence below it

Editorial fingerprint (mandatory):
Include at least one moment where the writing shows editorial judgment:
- A telling detail that others would omit
- An unexpected contextual connection
- A phrase that captures the significance in a way a summary cannot
This is what separates journalism from reporting.

---

PRIMARY KEYWORD EXTRACTION

Do not guess. Do not use a preset keyword.
In URL mode, derive the keyword from URL-derived title/content first.

Step 1: What is the single most specific subject of this article?
Step 2: How would a reader search for this in {$langName} on Google?
Step 3: Is this keyword confirmed in the source content?
If yes → this is the primary keyword. If no → find the closest confirmed term.

Use the selected keyword in: title, first paragraph, one subheading.
Secondary keywords: 2-3 natural variations. Use each once, only where natural. Never force.
Primary keyword usage should remain natural (typically 3-6 uses in full body).

---

LENGTH WITH DOUBLE QA LOOP

Target: article body should exceed 600 words wherever the source permits.
Thin-source exception: 450+ words is acceptable if accuracy is preserved.

QA PASS 1: Run the full checklist below. Fix all failing items. Count words.
IF UNDER TARGET: Expand weakest sections using confirmed source details or permitted general context. Do not add filler.
QA PASS 2 (mandatory after any expansion): Re-run full checklist from scratch. Fix any new issues.

---

INTERNAL QA CHECKLIST (run at Pass 1 and Pass 2)

✔ No invented numbers, specs, or source-specific claims
✔ Tier 2 general context does not exceed 15% of body
✔ Headline names subject, states change, signals impact, passes click test
✔ No filler sentences — every sentence adds new information
✔ No AI writing patterns or robotic transitions
✔ No repeated phrases beyond allowed limits
✔ No repeated ideas across paragraphs
✔ Sentence structure varied throughout — no 3 consecutive same-length sentences
✔ No obvious keyword stuffing or unnatural phrase repetition
✔ Editorial fingerprint present — one moment of clear editorial judgment
✔ Each section feels editorially distinct in tone
✔ Subheadings are in {$langName} and reflect actual section content (not generic labels)
✔ Language voice guide followed for {$langName}
✔ Sourcing is explicit and neutral; any publication name usage is factual/non-promotional
✔ Primary keyword used correctly in title, lead, one subheading
✔ Word count verified above 600 (or 450+ with justified thin-source note)
✔ All HTML tags opened and closed correctly
✔ JSON is valid and complete
✔ Final readability pass: read aloud mentally — does it flow like a human wrote it?
{$qaExtras}

---

IMAGE KEYWORDS

Identify 3-5 specific English nouns from the article topic for high-quality Unsplash photo search.
Use specific nouns: "smartphone", "solar-panel", "stock-market" — not vague terms like "technology" or "innovation".

---

OUTPUT: Return ONLY this JSON object — no markdown, no code fences, no explanation before or after:

{
  "title": "Factual, specific headline in {$langName} — max 80 chars, no clickbait, no question marks",
  "slug": "lowercase-ascii-kebab-slug-max-80-chars-locale-safe",
  "excerpt": "2-3 sentences in {$langName}. Compelling news summary. Max 200 chars.",
  "body": "<p>Lead...</p><h2>...</h2><p>...</p><h2>...</h2><p>...</p><h2>...</h2><p>...</p><p>Closing.</p>",
  "meta_title": "SEO title in {$langName} — max 60 chars",
  "meta_description": "Natural-language Google snippet in {$langName} — max 160 chars, written to drive clicks",
  "image_keywords": "specific english nouns for photo search"
}
USER;

        return [$system, $user];
    }

    /**
     * @return array<int, array{source_id: ?string, title: string, url: ?string, claim_note: ?string}>|null
     */
    private function sourcesForGeneration(AiArticleGeneration $gen): ?array
    {
        $sources = [];
        $sourceUrl = trim((string) ($gen->source_url ?? ''));
        if ($sourceUrl !== '') {
            $sources[] = [
                'source_id' => 'S1',
                'title' => trim((string) ($gen->source_title ?? '')) ?: (string) parse_url($sourceUrl, PHP_URL_HOST),
                'url' => $sourceUrl,
                'claim_note' => 'Primary source used during article generation.',
            ];
        }

        $normalized = ArticleSources::normalize($sources);

        return $normalized !== [] ? $normalized : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function callClaude(string $userPrompt, string $systemPrompt): array
    {
        if (Setting::get('bot_mock_mode', config('services.anthropic.mock', false))) {
            return $this->mockResponse($userPrompt);
        }

        $model     = Setting::get('bot_model', config('novaranews.bot_model', self::MODEL_DEFAULT));
        $maxTokens = (int) Setting::get('bot_max_tokens', 6144);
        $tools     = $this->buildTools();

        $messages = [
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $totalInputTokens  = 0;
        $totalOutputTokens = 0;
        $finalText         = null;
        $maxRounds         = 5; // cap tool-use rounds to prevent runaway loops

        for ($round = 0; $round < $maxRounds; $round++) {
            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'tools'      => $tools,
                'messages'   => $messages,
            ]);

            if (! $response->successful()) {
                $status = $response->status();
                // 429 Rate limit — throw a distinct exception so ThrottlesExceptions
                // middleware can catch it and pause the job queue for a cool-down period.
                if ($status === 429) {
                    throw new \RuntimeException("Anthropic rate limit (429): {$response->body()}");
                }
                throw new \RuntimeException("Anthropic API error {$status}: {$response->body()}");
            }

            $data = $response->json();

            $totalInputTokens  += $data['usage']['input_tokens'] ?? 0;
            $totalOutputTokens += $data['usage']['output_tokens'] ?? 0;

            $stopReason = $data['stop_reason'] ?? 'end_turn';
            $content    = $data['content'] ?? [];

            // Collect any text produced in this round
            foreach ($content as $block) {
                if (($block['type'] ?? '') === 'text' && ($block['text'] ?? '') !== '') {
                    $finalText = $block['text'];
                }
            }

            // No tool calls — conversation is done
            if ($stopReason !== 'tool_use') {
                break;
            }

            // Collect tool_use blocks
            $toolUseBlocks = array_values(array_filter(
                $content,
                fn (array $b): bool => ($b['type'] ?? '') === 'tool_use'
            ));

            if (empty($toolUseBlocks)) {
                break;
            }

            // Append assistant turn (with tool_use blocks) to conversation
            $messages[] = ['role' => 'assistant', 'content' => $content];

            // Execute each tool and collect results
            $toolResults = [];
            foreach ($toolUseBlocks as $toolUse) {
                $toolId    = $toolUse['id'] ?? '';
                $toolName  = $toolUse['name'] ?? '';
                $toolInput = $toolUse['input'] ?? [];

                try {
                    $result = $this->executeTool($toolName, $toolInput);
                } catch (\Throwable $e) {
                    $result = "Tool execution error: {$e->getMessage()}";
                }

                Log::info("ClaudeGenerator: tool '{$toolName}' called", [
                    'url'         => $toolInput['url'] ?? '',
                    'result_len'  => strlen($result),
                ]);

                $toolResults[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => $toolId,
                    'content'     => $result,
                ];
            }

            // Append tool results as a user turn and loop
            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        // Return a shape compatible with what generate() already expects
        return [
            'content' => [['type' => 'text', 'text' => $finalText ?? '']],
            'usage'   => [
                'input_tokens'  => $totalInputTokens,
                'output_tokens' => $totalOutputTokens,
            ],
        ];
    }

    // ── Tool definitions ─────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTools(): array
    {
        return [
            [
                'name'        => 'fetch_url',
                'description' => 'Fetch the full text content of a web page. '
                    .'Use this to retrieve the complete article from the source URL before writing, '
                    .'and to verify claims from official press releases or primary sources. '
                    .'Returns plain text extracted from the page HTML (up to 8 000 characters). '
                    .'Always call this on the SOURCE_URL first before writing the article.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'url' => [
                            'type'        => 'string',
                            'description' => 'The full URL to fetch (must begin with https:// or http://).',
                        ],
                    ],
                    'required' => ['url'],
                ],
            ],
        ];
    }

    private function executeTool(string $name, array $input): string
    {
        return match ($name) {
            'fetch_url' => $this->fetchUrlText((string) ($input['url'] ?? '')),
            default     => "Unknown tool: {$name}",
        };
    }

    /**
     * Fetch a URL and return its readable text content.
     */
    private function fetchUrlText(string $url): string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return "Error: invalid URL '{$url}'";
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return "Error: only http/https URLs are supported";
        }

        try {
            $response = Http::timeout(15)
                ->connectTimeout(8)
                ->withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Cache-Control'   => 'no-cache',
                ])
                ->get($url);

            if (! $response->successful()) {
                return "Error: HTTP {$response->status()} fetching {$url}";
            }

            $html = $response->body();
            if (strlen($html) < 100) {
                return "Error: empty or near-empty response from {$url}";
            }

            $text = $this->extractTextFromHtml($html, 4000);

            if (strlen(trim($text)) < 50) {
                return "Error: could not extract readable text from {$url}";
            }

            return "FETCHED CONTENT FROM: {$url}\n\n{$text}";

        } catch (\Throwable $e) {
            return "Error fetching {$url}: {$e->getMessage()}";
        }
    }

    /**
     * Strip HTML boilerplate and return clean readable text up to $maxChars.
     */
    private function extractTextFromHtml(string $html, int $maxChars = 8000): string
    {
        // Remove non-content elements
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/si', '', $html) ?? $html;
        $html = preg_replace('/<nav\b[^>]*>.*?<\/nav>/si', '', $html) ?? $html;
        $html = preg_replace('/<header\b[^>]*>.*?<\/header>/si', '', $html) ?? $html;
        $html = preg_replace('/<footer\b[^>]*>.*?<\/footer>/si', '', $html) ?? $html;
        $html = preg_replace('/<aside\b[^>]*>.*?<\/aside>/si', '', $html) ?? $html;
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;

        // Convert block elements to newlines before tag stripping
        $html = preg_replace('/<\/?(p|div|h[1-6]|li|br|tr|blockquote|section|article)[^>]*>/i', "\n", $html) ?? $html;

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        if (mb_strlen($text) > $maxChars) {
            $text = mb_substr($text, 0, $maxChars);
        }

        return $text;
    }

    private function mockResponse(string $prompt): array
    {
        preg_match('/Title: (.+)/m', $prompt, $titleMatch);
        preg_match('/Language: (\w+)/m', $prompt, $langMatch);

        $title = trim($titleMatch[1] ?? 'Test Article');
        $lang = trim($langMatch[1] ?? 'English');
        $slug = Str::slug(substr($title, 0, 60)).'-'.rand(100, 999);

        $body = "<p>This is a mock article generated for testing purposes in {$lang}. The original news topic was: {$title}</p>"
            . "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>"
            . "<h2>Background</h2>"
            . "<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident.</p>"
            . "<p>Sunt in culpa qui officia deserunt mollit anim id est laborum. This mock article demonstrates the full pipeline is working correctly.</p>";

        $json = json_encode([
            'title' => mb_substr($title, 0, 90),
            'slug' => $slug,
            'excerpt' => "Mock excerpt for: {$title}. This article was generated in test mode.",
            'body' => $body,
            'meta_title' => mb_substr($title, 0, 60),
            'meta_description' => "Mock meta description for testing. Topic: {$title}",
        ]);

        return [
            'content' => [['text' => $json]],
            'usage' => ['input_tokens' => 0, 'output_tokens' => 0],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseResponse(string $text): array
    {
        // Strip possible markdown code fences
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text ?? '');

        // Find first { ... } block
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');

        if ($start === false || $end === false) {
            throw new \RuntimeException('Claude response did not contain a JSON object');
        }

        $json = substr($text, $start, $end - $start + 1);

        // Attempt 1: parse as-is (valid JSON, no repair needed)
        $data = json_decode($json, true, 512);
        if (is_array($data)) {
            return $this->normalizeParsedPayload($data);
        }

        // Attempt 2: fix newlines in all fields first, then fix unescaped quotes in body using
        // positional extraction (immune to " state confusion from HTML attributes).
        $fixed = $this->repairBodyField($this->repairJsonNewlines($json));
        $data  = json_decode($fixed, true, 512);
        if (is_array($data)) {
            return $this->normalizeParsedPayload($data);
        }

        // Attempt 3: only body repair on the original (skips newline repair in case it mangled something)
        $fixed2 = $this->repairBodyField($json);
        $data   = json_decode($fixed2, true, 512);
        if (is_array($data)) {
            return $this->normalizeParsedPayload($data);
        }

        // Attempt 4: newlines only (original behaviour, for cases without HTML quotes)
        $fixed3 = $this->repairJsonNewlines($json);
        $data   = json_decode($fixed3, true, 512);
        if (is_array($data)) {
            return $this->normalizeParsedPayload($data);
        }

        throw new \RuntimeException(
            'Claude response JSON parse error: '.json_last_error_msg().' — raw: '.mb_substr($json, 0, 300)
        );
    }

    /**
     * @return array<string, string>
     */
    private function normalizeParsedPayload(array $data): array
    {
        $translation = isset($data['translation']) && is_array($data['translation'])
            ? $data['translation']
            : null;

        if ($translation !== null) {
            return [
                'title' => (string) ($translation['title'] ?? $data['title'] ?? ''),
                'slug' => (string) ($translation['slug'] ?? $data['slug'] ?? ''),
                'excerpt' => (string) ($translation['excerpt'] ?? $data['excerpt'] ?? ''),
                'body' => (string) ($translation['body'] ?? $data['body'] ?? ''),
                'meta_title' => (string) ($translation['meta_title'] ?? $data['meta_title'] ?? ''),
                'meta_description' => (string) ($translation['meta_description'] ?? $data['meta_description'] ?? ''),
                'image_keywords' => (string) ($data['image_keywords'] ?? ''),
            ];
        }

        return [
            'title' => (string) ($data['title'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'excerpt' => (string) ($data['excerpt'] ?? ''),
            'body' => (string) ($data['body'] ?? ''),
            'meta_title' => (string) ($data['meta_title'] ?? ''),
            'meta_description' => (string) ($data['meta_description'] ?? ''),
            'image_keywords' => (string) ($data['image_keywords'] ?? ''),
        ];
    }

    private function buildPromptFromDocsTemplate(
        string $locale,
        string $categoryKey,
        string $sourceUrl,
        string $sourceTitle,
        string $sourceContent,
        string $today
    ): ?string {
        $template = null;
        $candidates = [
            base_path('resources/prompts/claude-prompt-news.txt'),
        ];

        foreach ($candidates as $path) {
            if (! is_file($path)) {
                continue;
            }
            $loaded = @file_get_contents($path);
            if (is_string($loaded) && trim($loaded) !== '') {
                $template = $loaded;
                break;
            }
        }

        if (! is_string($template) || trim($template) === '') {
            return null;
        }

        $sourceUrlLine = $sourceUrl !== '' ? $sourceUrl : '[MISSING_SOURCE_URL]';

        $template = preg_replace('/^LANGUAGE\s*:\s*.*$/m', 'LANGUAGE        : '.$locale, $template, 1) ?? $template;
        $template = preg_replace('/^CATEGORY\s*:\s*.*$/m', 'CATEGORY        : '.$categoryKey, $template, 1) ?? $template;
        $template = preg_replace('/^SOURCE_URL\s*:\s*.*$/m', 'SOURCE_URL      : '.$sourceUrlLine, $template, 1) ?? $template;
        $template = preg_replace('/^MODE\s*:\s*.*$/m', 'MODE            : v6 Verified Newsroom Mode (Google News + SEO + multi-source fact-check + native editorial quality)', $template, 1) ?? $template;
        $template = str_replace(
            ['[CATEGORY]', '[LANGUAGE]', '[LANGUAGE_LOCALE]'],
            [$categoryKey, $locale, $locale],
            $template
        );

        $sourcePacket = "SOURCE_PACKET\n".
            "-------------\n".
            "SOURCE_TITLE: ".trim($sourceTitle)."\n".
            "SOURCE_URL: {$sourceUrlLine}\n".
            "TODAY: {$today}\n".
            "URL_EXTRACTED_CONTENT:\n".mb_substr(trim($sourceContent), 0, 12000)."\n";

        return $template."\n\n".$sourcePacket;
    }

    /**
     * Positional repair of the "body" field in Claude's JSON output.
     *
     * Claude sometimes generates HTML containing unescaped double quotes inside attribute values
     * (e.g. href="url"), which breaks the JSON string. Since we know the fixed key order
     * (body is always followed by meta_title, meta_description, or image_keywords), we locate
     * the body value by its surrounding anchors, extract the raw bytes, then properly re-escape
     * every special character (unescaped quotes, literal newlines, etc.) without double-escaping
     * sequences that are already valid.
     */
    private function repairBodyField(string $json): string
    {
        // Locate the opening of the body value: "body": "
        if (! preg_match('/"body"\s*:\s*"/', $json, $startMatch, PREG_OFFSET_CAPTURE)) {
            return $json; // no body field — nothing to repair
        }

        $valueOpenPos = $startMatch[0][1] + strlen($startMatch[0][0]);

        // Locate the closing anchor: the `"` that ends the body value, immediately followed
        // by a comma + known next JSON key. We search from valueOpenPos onward.
        if (! preg_match(
            '/",\s*"(?:meta_title|meta_description|image_keywords|title|slug|excerpt)"/',
            $json,
            $endMatch,
            PREG_OFFSET_CAPTURE,
            $valueOpenPos
        )) {
            return $json; // can't find end anchor — leave unchanged
        }

        $closeQuotePos = $endMatch[0][1]; // position of the closing `"` of the body value

        // Extract the raw body content (may contain unescaped quotes, newlines, etc.)
        $rawBody = substr($json, $valueOpenPos, $closeQuotePos - $valueOpenPos);

        // Re-escape the raw body content for use inside a JSON double-quoted string.
        // Walk byte-by-byte; when we see `\` followed by any character, pass the pair
        // through unchanged (already-valid escape sequence). Otherwise escape as needed.
        $escaped = '';
        $len     = strlen($rawBody);
        $i       = 0;

        while ($i < $len) {
            $ch = $rawBody[$i];

            if ($ch === '\\' && $i + 1 < $len) {
                // Already-escaped pair (e.g. \n, \", \\, \u0041) — keep as-is
                $escaped .= $ch . $rawBody[$i + 1];
                $i       += 2;
                continue;
            }

            if ($ch === '"')       { $escaped .= '\\"';  $i++; continue; }
            if ($ch === "\n")      { $escaped .= '\\n';  $i++; continue; }
            if ($ch === "\r")      { $escaped .= '\\r';  $i++; continue; }
            if ($ch === "\t")      { $escaped .= '\\t';  $i++; continue; }
            if (ord($ch) < 0x20)  { $i++; continue; } // strip other control chars

            $escaped .= $ch;
            $i++;
        }

        // Reconstruct: everything before the body value + escaped content + closing anchor onward
        return substr($json, 0, $valueOpenPos) . $escaped . substr($json, $closeQuotePos);
    }

    /**
     * Replace literal newlines/tabs inside JSON string values with their escape sequences.
     * Uses a simple state machine; works correctly for fields that do NOT contain unescaped
     * double quotes (title, slug, excerpt, meta_title, meta_description, image_keywords).
     * For the body field use repairBodyField() instead.
     */
    private function repairJsonNewlines(string $json): string
    {
        $result  = '';
        $inStr   = false;
        $escaped = false;
        $len     = strlen($json);

        for ($i = 0; $i < $len; $i++) {
            $ch = $json[$i];

            if ($escaped) {
                $result  .= $ch;
                $escaped  = false;
                continue;
            }

            if ($ch === '\\' && $inStr) {
                $result  .= $ch;
                $escaped  = true;
                continue;
            }

            if ($ch === '"') {
                $inStr  = ! $inStr;
                $result .= $ch;
                continue;
            }

            if ($inStr) {
                if ($ch === "\n") { $result .= '\\n'; continue; }
                if ($ch === "\r") { $result .= '\\r'; continue; }
                if ($ch === "\t") { $result .= '\\t'; continue; }
                if (ord($ch) < 0x20) { continue; }
            }

            $result .= $ch;
        }

        return $result;
    }

    private function resolveCategory(?string $key): ?Category
    {
        if ($key) {
            return Category::query()->where('key', $key)->first();
        }

        return null;
    }

    private function uniqueSlug(string $base, string $locale): string
    {
        $base = Str::slug($base);
        if (! $base) {
            $base = 'article-'.time();
        }

        $slug = $base;
        $i = 1;

        $reserved = ReservedPathSegments::all();
        $categorySlugsTaken = CategoryTranslation::query()->where('locale', $locale)->pluck('slug')->all();

        while (
            in_array($slug, $reserved, true) ||
            in_array($slug, $categorySlugsTaken, true) ||
            ArticleTranslation::query()->where('locale', $locale)->where('slug', $slug)->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function estimateCost(int $inputTokens, int $outputTokens): float
    {
        return round(
            ($inputTokens / 1_000_000 * self::COST_INPUT_PER_M) +
            ($outputTokens / 1_000_000 * self::COST_OUTPUT_PER_M),
            6
        );
    }
}
