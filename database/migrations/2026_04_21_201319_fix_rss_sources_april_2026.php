<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RSS source audit — April 2026
 *
 * Verified fixes based on live feed checks:
 *
 * 1. hnrss.org/frontpage          → ECONNREFUSED on server → replaced with Engadget (EN, technology)
 * 2. muyinteresante.es/rss         → Redirects to okdiario; content is science/archaeology, NOT tech
 *                                     → replaced with HardZone (ES, hardware/technology)
 * 3. heise.de/ix/rss/news-atom.xml → Was "Heise KI" (DE AI) but iX is a developer magazine, not AI.
 *                                     Golem KI already covers DE AI well. This row reassigned to
 *                                     software category with corrected URL (heise.de/ix/feed.xml).
 * 4. Automotive sources            → "automotive" is not a supported category in the article generator.
 *                                     All automotive sources deactivated to prevent silent failures.
 * 5. Xataka IA duplicate           → xataka.com/index.xml used for both ES AI and ES technology.
 *                                     AI row now uses Genbeta (software/AI content, ES).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        // ── 1. Hacker News → Engadget ────────────────────────────────────────
        // hnrss.org returns ECONNREFUSED from datacenter IPs.
        DB::table('news_sources')
            ->where('url', 'https://hnrss.org/frontpage')
            ->update([
                'url'  => 'https://www.engadget.com/rss.xml',
                'name' => 'Engadget',
            ]);

        // ── 2. Muy Interesante → HardZone ────────────────────────────────────
        // muyinteresante.es now redirects to okdiario; content is science/archaeology, not tech.
        // HardZone is Spanish hardware + technology publication (verified working, correct content).
        DB::table('news_sources')
            ->whereIn('url', [
                'https://www.muyinteresante.es/rss',
                'https://muyinteresante.okdiario.com/rss',
            ])
            ->update([
                'url'  => 'https://hardzone.es/feed/',
                'name' => 'HardZone',
            ]);

        // ── 3. Heise iX — wrong category, correct URL ────────────────────────
        // Row was originally "Heise KI" (DE, artificial-intelligence) but got replaced with
        // heise.de/ix/rss/news-atom.xml (iX = Heise developer magazine, not AI).
        // Golem KI already covers DE AI. Reassign iX to software category where it belongs.
        DB::table('news_sources')
            ->whereIn('url', [
                'https://www.heise.de/ix/rss/news-atom.xml',
                'https://www.heise.de/ix/feed.xml',
            ])
            ->where('locale', 'de')
            ->update([
                'url'          => 'https://www.heise.de/ix/feed.xml',
                'name'         => 'Heise iX',
                'category_key' => 'software',
            ]);

        // ── 4. Deactivate automotive sources ─────────────────────────────────
        // "automotive" is not a supported category in the article generator
        // (supported: artificial-intelligence | technology | mobile | game | software | hardware).
        // These sources queue jobs that always fail with "Cannot resolve category: automotive".
        DB::table('news_sources')
            ->where('category_key', 'automotive')
            ->update(['is_active' => false]);

        // ── 5. Fix Xataka IA duplicate (ES AI category) ───────────────────────
        // Both "Xataka IA" and "Xataka" (technology) pointed to xataka.com/index.xml.
        // Cross-source URL dedup meant one category starved. Give AI row its own distinct source.
        // Genbeta covers software + AI topics in Spanish (verified working).
        DB::table('news_sources')
            ->where('url', 'https://www.xataka.com/index.xml')
            ->where('locale', 'es')
            ->where('category_key', 'artificial-intelligence')
            ->update([
                'url'  => 'https://www.genbeta.com/index.xml',
                'name' => 'Genbeta IA',
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('news_sources')) {
            return;
        }

        DB::table('news_sources')
            ->where('url', 'https://www.engadget.com/rss.xml')
            ->where('locale', 'en')
            ->update(['url' => 'https://hnrss.org/frontpage', 'name' => 'Hacker News']);

        DB::table('news_sources')
            ->where('url', 'https://hardzone.es/feed/')
            ->update(['url' => 'https://www.muyinteresante.es/rss', 'name' => 'Muy Interesante']);

        DB::table('news_sources')
            ->where('url', 'https://www.heise.de/ix/feed.xml')
            ->where('locale', 'de')
            ->update([
                'url'          => 'https://www.heise.de/ix/rss/news-atom.xml',
                'name'         => 'Heise KI',
                'category_key' => 'artificial-intelligence',
            ]);

        DB::table('news_sources')
            ->where('category_key', 'automotive')
            ->update(['is_active' => true]);

        DB::table('news_sources')
            ->where('url', 'https://www.genbeta.com/index.xml')
            ->where('locale', 'es')
            ->where('category_key', 'artificial-intelligence')
            ->update(['url' => 'https://www.xataka.com/index.xml', 'name' => 'Xataka IA']);
    }
};
