<?php

namespace App\Support;

/**
 * Maps known news-site domains to their readable publication names.
 * Used in the AI-disclosure block to show which sources were referenced.
 */
class PublicationNames
{
    private static array $map = [
        // International wire services
        'reuters.com'          => 'Reuters',
        'apnews.com'           => 'AP News',
        'afp.com'              => 'AFP',
        'efe.com'              => 'EFE',
        'dpa.com'              => 'DPA',

        // English-language outlets
        'bbc.com'              => 'BBC',
        'bbc.co.uk'            => 'BBC',
        'cnn.com'              => 'CNN',
        'theguardian.com'      => 'The Guardian',
        'nytimes.com'          => 'The New York Times',
        'wsj.com'              => 'The Wall Street Journal',
        'bloomberg.com'        => 'Bloomberg',
        'ft.com'               => 'Financial Times',
        'economist.com'        => 'The Economist',
        'washingtonpost.com'   => 'The Washington Post',
        'techcrunch.com'       => 'TechCrunch',
        'theverge.com'         => 'The Verge',
        'wired.com'            => 'Wired',
        'arstechnica.com'      => 'Ars Technica',
        'engadget.com'         => 'Engadget',
        'businessinsider.com'  => 'Business Insider',
        'forbes.com'           => 'Forbes',

        // German outlets
        'dw.com'               => 'Deutsche Welle',
        'spiegel.de'           => 'Der Spiegel',
        'zeit.de'              => 'Die Zeit',
        'faz.net'              => 'FAZ',
        'sueddeutsche.de'      => 'Süddeutsche Zeitung',
        'handelsblatt.com'     => 'Handelsblatt',
        'chip.de'              => 'Chip',
        'heise.de'             => 'Heise Online',

        // French outlets
        'lemonde.fr'           => 'Le Monde',
        'lefigaro.fr'          => 'Le Figaro',
        'liberation.fr'        => 'Libération',
        'leparisien.fr'        => 'Le Parisien',

        // Spanish outlets
        'elpais.com'           => 'El País',
        'elmundo.es'           => 'El Mundo',
        'abc.es'               => 'ABC',
        'lavanguardia.com'     => 'La Vanguardia',

        // Turkish outlets
        'hurriyet.com.tr'      => 'Hürriyet',
        'sabah.com.tr'         => 'Sabah',
        'milliyet.com.tr'      => 'Milliyet',
        'haberturk.com'        => 'Habertürk',
        'ntv.com.tr'           => 'NTV',
        'cnnturk.com'          => 'CNN Türk',
        'sozcu.com.tr'         => 'Sözcü',
        'cumhuriyet.com.tr'    => 'Cumhuriyet',
        'aa.com.tr'            => 'Anadolu Ajansı',
        'donanimhaber.com'     => 'Donanım Haber',
        'chip.com.tr'          => 'Chip Türkiye',
        'webtekno.com'         => 'Webtekno',
        'shiftdelete.net'      => 'ShiftDelete',
    ];

    /**
     * Return a human-readable publication name for the given URL.
     * Falls back to cleaned-up domain (strips www., capitalizes).
     */
    public static function fromUrl(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $host = preg_replace('/^www\./', '', $host);

        if (isset(self::$map[$host])) {
            return self::$map[$host];
        }

        // Fallback: strip TLD, capitalize
        $parts = explode('.', $host);
        return ucfirst($parts[0] ?? $host);
    }

    /**
     * Deduplicated list of publication names from an array of source records.
     *
     * @param  array<array{url?: string, label?: string}>  $sources
     * @return string[]
     */
    public static function fromSources(array $sources): array
    {
        $names = [];
        foreach ($sources as $source) {
            $url = $source['url'] ?? null;
            if (! is_string($url) || ! str_starts_with($url, 'http')) {
                continue;
            }
            $name = self::fromUrl($url);
            $names[$name] = true; // deduplicate
        }
        return array_keys($names);
    }
}
