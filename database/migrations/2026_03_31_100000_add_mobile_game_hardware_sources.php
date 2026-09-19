<?php

use App\Models\NewsSource;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds RSS sources for mobile, game, and hardware categories (all 5 locales).
 * Gadgets category slug updated from 'cihazlar-donanim' to 'cihazlar' (TR).
 */
return new class extends Migration
{
    public function up(): void
    {
        $sources = [

            // ══════════════════════════════════════════════════════════════
            // MOBILE
            // ══════════════════════════════════════════════════════════════

            // ── ENGLISH ──────────────────────────────────────────────────
            ['name' => '9to5Google',              'url' => 'https://9to5google.com/feed/',                                      'locale' => 'en', 'category_key' => 'mobile'],
            ['name' => 'MacRumors',               'url' => 'https://feeds.macrumors.com/MacRumors',                             'locale' => 'en', 'category_key' => 'mobile'],
            ['name' => 'Android Authority',       'url' => 'https://www.androidauthority.com/feed/',                            'locale' => 'en', 'category_key' => 'mobile'],
            ['name' => 'The Verge Mobile',        'url' => 'https://www.theverge.com/rss/mobile/index.xml',                    'locale' => 'en', 'category_key' => 'mobile'],
            ['name' => '9to5Mac',                 'url' => 'https://9to5mac.com/feed/',                                        'locale' => 'en', 'category_key' => 'mobile'],

            // ── TURKISH ───────────────────────────────────────────────────
            ['name' => 'Webtekno Mobil',          'url' => 'https://www.webtekno.com/rss.xml',                                 'locale' => 'tr', 'category_key' => 'mobile'],
            ['name' => 'ShiftDelete Mobil',       'url' => 'https://shiftdelete.net/feed',                                     'locale' => 'tr', 'category_key' => 'mobile'],
            ['name' => 'Technopat',               'url' => 'https://www.technopat.net/feed/',                                  'locale' => 'tr', 'category_key' => 'mobile'],

            // ── GERMAN ────────────────────────────────────────────────────
            ['name' => 'Heise Mobil',             'url' => 'https://www.heise.de/mobil/rss/heise-mobil-atom.xml',              'locale' => 'de', 'category_key' => 'mobile'],
            ['name' => 'AndroidPIT DE',           'url' => 'https://www.androidpit.de/feed/rss/news',                          'locale' => 'de', 'category_key' => 'mobile'],
            ['name' => 'Golem Mobil',             'url' => 'https://rss.golem.de/rss.php?r=mo-ms&feed=RSS2.0',                'locale' => 'de', 'category_key' => 'mobile'],

            // ── FRENCH ────────────────────────────────────────────────────
            ['name' => 'FrAndroid',               'url' => 'https://www.frandroid.com/feed',                                   'locale' => 'fr', 'category_key' => 'mobile'],
            ['name' => 'iGeneration',             'url' => 'https://www.igen.fr/rss.xml',                                      'locale' => 'fr', 'category_key' => 'mobile'],
            ['name' => 'AndroidMag FR',           'url' => 'https://www.androidmag.fr/feed/',                                  'locale' => 'fr', 'category_key' => 'mobile'],

            // ── SPANISH ───────────────────────────────────────────────────
            ['name' => 'Xataka Android',          'url' => 'https://www.xatakandroid.com/rss/',                                'locale' => 'es', 'category_key' => 'mobile'],
            ['name' => 'iPhoneros',               'url' => 'https://www.iphoneros.com/feed',                                   'locale' => 'es', 'category_key' => 'mobile'],
            ['name' => 'Xataka Móvil',            'url' => 'https://www.xatakamovil.com/rss/',                                 'locale' => 'es', 'category_key' => 'mobile'],

            // ══════════════════════════════════════════════════════════════
            // GAME
            // ══════════════════════════════════════════════════════════════

            // ── ENGLISH ──────────────────────────────────────────────────
            ['name' => 'Eurogamer',               'url' => 'https://www.eurogamer.net/?format=rss',                            'locale' => 'en', 'category_key' => 'game'],
            ['name' => 'Ars Technica Gaming',     'url' => 'https://feeds.arstechnica.com/arstechnica/gaming',                 'locale' => 'en', 'category_key' => 'game'],
            ['name' => 'The Verge Gaming',        'url' => 'https://www.theverge.com/rss/gaming/index.xml',                   'locale' => 'en', 'category_key' => 'game'],
            ['name' => 'PC Gamer',                'url' => 'https://www.pcgamer.com/rss/',                                     'locale' => 'en', 'category_key' => 'game'],
            ['name' => 'Rock Paper Shotgun',      'url' => 'https://www.rockpapershotgun.com/feed',                            'locale' => 'en', 'category_key' => 'game'],

            // ── TURKISH ───────────────────────────────────────────────────
            ['name' => 'Oyungezer Online',        'url' => 'https://www.oyungezer.com.tr/feed/',                               'locale' => 'tr', 'category_key' => 'game'],
            ['name' => 'Webtekno Oyun',           'url' => 'https://www.webtekno.com/rss.xml',                                 'locale' => 'tr', 'category_key' => 'game'],
            ['name' => 'Tamindir Oyun',           'url' => 'https://www.tamindir.com/feed/',                                   'locale' => 'tr', 'category_key' => 'game'],

            // ── GERMAN ────────────────────────────────────────────────────
            ['name' => 'GameStar',                'url' => 'https://www.gamestar.de/rss/news/',                                'locale' => 'de', 'category_key' => 'game'],
            ['name' => 'GamePro DE',              'url' => 'https://www.gamepro.de/rss/news/',                                 'locale' => 'de', 'category_key' => 'game'],
            ['name' => 'PC Games DE',             'url' => 'https://www.pcgames.de/rss/news/',                                 'locale' => 'de', 'category_key' => 'game'],

            // ── FRENCH ────────────────────────────────────────────────────
            ['name' => 'JeuxVideo.com',           'url' => 'https://www.jeuxvideo.com/rss/rss-news.xml',                      'locale' => 'fr', 'category_key' => 'game'],
            ['name' => 'Gameblog FR',             'url' => 'https://www.gameblog.fr/rss',                                     'locale' => 'fr', 'category_key' => 'game'],
            ['name' => 'Numerama Gaming',         'url' => 'https://www.numerama.com/feed/',                                   'locale' => 'fr', 'category_key' => 'game'],

            // ── SPANISH ───────────────────────────────────────────────────
            ['name' => '3DJuegos',                'url' => 'https://www.3djuegos.com/rss/noticias.php',                        'locale' => 'es', 'category_key' => 'game'],
            ['name' => 'Vandal',                  'url' => 'https://vandal.elespanol.com/rss/portada.xml',                    'locale' => 'es', 'category_key' => 'game'],
            ['name' => 'MeriStation',             'url' => 'https://www.meristation.com/rss/portada/news',                    'locale' => 'es', 'category_key' => 'game'],

            // ══════════════════════════════════════════════════════════════
            // HARDWARE
            // ══════════════════════════════════════════════════════════════

            // ── ENGLISH ──────────────────────────────────────────────────
            ['name' => "Tom's Hardware",          'url' => 'https://www.tomshardware.com/feeds/all',                           'locale' => 'en', 'category_key' => 'hardware'],
            ['name' => 'AnandTech',               'url' => 'https://www.anandtech.com/rss/news',                               'locale' => 'en', 'category_key' => 'hardware'],
            ['name' => 'ExtremeTech',             'url' => 'https://www.extremetech.com/feed',                                 'locale' => 'en', 'category_key' => 'hardware'],
            ['name' => 'Ars Technica Hardware',   'url' => 'https://feeds.arstechnica.com/arstechnica/gadgets',                'locale' => 'en', 'category_key' => 'hardware'],
            ['name' => 'NotebookCheck',           'url' => 'https://www.notebookcheck.net/News.8.0.html?feed=rss',            'locale' => 'en', 'category_key' => 'hardware'],

            // ── TURKISH ───────────────────────────────────────────────────
            ['name' => 'Donanımhaber',            'url' => 'https://www.donanimhaber.com/rss/',                                'locale' => 'tr', 'category_key' => 'hardware'],
            ['name' => 'Chip TR Donanım',         'url' => 'https://www.chip.com.tr/rss/',                                    'locale' => 'tr', 'category_key' => 'hardware'],
            ['name' => 'Technopat Donanım',       'url' => 'https://www.technopat.net/feed/',                                  'locale' => 'tr', 'category_key' => 'hardware'],

            // ── GERMAN ────────────────────────────────────────────────────
            ['name' => 'Hardwareluxx',            'url' => 'https://www.hardwareluxx.de/community/backend.php/threads?prefix_id=6', 'locale' => 'de', 'category_key' => 'hardware'],
            ['name' => 'Golem Hardware',          'url' => 'https://rss.golem.de/rss.php?r=hw&feed=RSS2.0',                  'locale' => 'de', 'category_key' => 'hardware'],
            ['name' => 'ComputerBase',            'url' => 'https://www.computerbase.de/feed/news.rss',                        'locale' => 'de', 'category_key' => 'hardware'],

            // ── FRENCH ────────────────────────────────────────────────────
            ['name' => "Tom's Hardware FR",       'url' => 'https://www.tomshardware.fr/feeds/all',                            'locale' => 'fr', 'category_key' => 'hardware'],
            ['name' => 'Hardware.fr',             'url' => 'https://www.hardware.fr/rss.xml',                                  'locale' => 'fr', 'category_key' => 'hardware'],
            ['name' => 'Canard PC Hardware',      'url' => 'https://www.canardpc.com/feed/',                                   'locale' => 'fr', 'category_key' => 'hardware'],

            // ── SPANISH ───────────────────────────────────────────────────
            ['name' => 'El Chapuzas Informático', 'url' => 'https://elchapuzasinformatico.com/feed/',                          'locale' => 'es', 'category_key' => 'hardware'],
            ['name' => 'Hardzone',                'url' => 'https://hardzone.es/feed/',                                        'locale' => 'es', 'category_key' => 'hardware'],
            ['name' => 'Xataka Componentes',      'url' => 'https://www.xataka.com/rss/',                                      'locale' => 'es', 'category_key' => 'hardware'],

        ];

        foreach ($sources as $data) {
            NewsSource::query()->firstOrCreate(
                ['url' => $data['url']],
                [
                    'name'         => $data['name'],
                    'locale'       => $data['locale'],
                    'category_key' => $data['category_key'],
                    'is_active'    => true,
                ]
            );
        }
    }

    public function down(): void
    {
        NewsSource::query()
            ->whereIn('category_key', ['mobile', 'game', 'hardware'])
            ->delete();
    }
};
