<?php

namespace App\Data;

class SeoPayload
{
    /** @param array<string, mixed> $extra @param array<string, mixed> $schemas */
    public function __construct(
        public readonly string $title = '',
        public readonly string $description = '',
        public readonly string $canonical = '',
        public readonly string $ogTitle = '',
        public readonly string $ogDescription = '',
        public readonly ?string $ogImageUrl = null,
        public readonly string $robots = '',
        public readonly ?string $intro = null,
        public readonly array $extra = [],
        public readonly array $schemas = [],
    ) {
    }

    /** @param array<string, mixed> $extra @param array<string, mixed> $schemas */
    public static function basic(
        string $title,
        string $description = '',
        string $canonical = '',
        string $ogTitle = '',
        string $ogDescription = '',
        ?string $ogImageUrl = null,
        string $robots = '',
        ?string $intro = null,
        array $extra = [],
        array $schemas = [],
    ): self {
        return new self(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageUrl: $ogImageUrl,
            robots: $robots,
            intro: $intro,
            extra: $extra,
            schemas: $schemas,
        );
    }

    public static function forCategory(
        string $title,
        string $description,
        string $canonical,
        string $ogTitle,
        string $ogDescription,
        ?string $ogImageUrl,
        string $robots,
        string $intro,
    ): self {
        return self::basic(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageUrl: $ogImageUrl,
            robots: $robots,
            intro: $intro,
        );
    }

    /** @param array<string, mixed> $extra @param array<string, mixed> $schemas */
    public static function forArticle(
        string $title,
        string $description,
        string $canonical,
        string $ogTitle,
        string $ogDescription,
        ?string $ogImageUrl,
        string $robots,
        array $extra = [],
        array $schemas = [],
    ): self {
        return self::basic(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageUrl: $ogImageUrl,
            robots: $robots,
            extra: $extra,
            schemas: $schemas,
        );
    }

    public static function forStaticPage(
        string $title,
        string $description,
        string $canonical,
        string $ogTitle,
        string $ogDescription,
        ?string $ogImageUrl,
        string $robots,
    ): self {
        return self::basic(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageUrl: $ogImageUrl,
            robots: $robots,
        );
    }

    public function extra(string $key, mixed $default = null): mixed
    {
        return $this->extra[$key] ?? $default;
    }

    public function schema(string $key, mixed $default = null): mixed
    {
        return $this->schemas[$key] ?? $default;
    }

    public function effectiveOgTitle(): string
    {
        return $this->ogTitle !== '' ? $this->ogTitle : $this->title;
    }

    public function effectiveOgDescription(): string
    {
        return $this->ogDescription !== '' ? $this->ogDescription : $this->description;
    }

    /** @return array<string, string> */
    public function toMetaArray(bool $includeDescription = true, bool $includeRobots = true): array
    {
        $meta = [];

        if ($includeDescription && $this->description !== '') {
            $meta['description'] = $this->description;
        }

        if ($includeRobots && $this->robots !== '') {
            $meta['robots'] = $this->robots;
        }

        return $meta;
    }

    /** @return array<string, string> */
    public function toOpenGraphArray(?string $type = null, ?string $url = null, bool $includeImage = true): array
    {
        $meta = [
            'og:title' => $this->effectiveOgTitle(),
        ];

        $description = $this->effectiveOgDescription();
        if ($description !== '') {
            $meta['og:description'] = $description;
        }

        if ($type !== null && $type !== '') {
            $meta['og:type'] = $type;
        }

        if ($url !== null && $url !== '') {
            $meta['og:url'] = $url;
        }

        if ($includeImage && $this->ogImageUrl) {
            $meta['og:image'] = $this->ogImageUrl;
        }

        return $meta;
    }

    /** @return array<string, string> */
    public function toTwitterArray(string $card = 'summary_large_image', bool $includeImage = true): array
    {
        $meta = [
            'twitter:card' => $card,
            'twitter:title' => $this->effectiveOgTitle(),
        ];

        $description = $this->effectiveOgDescription();
        if ($description !== '') {
            $meta['twitter:description'] = $description;
        }

        if ($includeImage && $this->ogImageUrl) {
            $meta['twitter:image'] = $this->ogImageUrl;
        }

        return $meta;
    }
}

