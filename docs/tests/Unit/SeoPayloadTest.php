<?php

namespace Tests\Unit;

use App\Data\SeoPayload;
use App\Support\SeoMetaVariant;
use PHPUnit\Framework\TestCase;

class SeoPayloadTest extends TestCase
{
    public function test_article_payload_converters_emit_expected_meta_sets(): void
    {
        $payload = SeoPayload::forArticle(
            title: 'Article Title - Novara News',
            description: 'Article summary for search and social cards.',
            canonical: 'https://novaranews.com/en/news/article-title',
            ogTitle: 'Custom OG Title',
            ogDescription: 'Custom OG Description',
            ogImageUrl: 'https://novaranews.com/og.jpg',
            robots: 'index, follow',
            extra: ['articleSection' => 'Technology'],
            schemas: ['article' => ['@type' => 'NewsArticle']],
        );

        $this->assertSame('Technology', $payload->extra('articleSection'));
        $this->assertSame('NewsArticle', $payload->schema('article')['@type']);
        $this->assertSame('Custom OG Title', $payload->effectiveOgTitle());
        $this->assertSame('Custom OG Description', $payload->effectiveOgDescription());

        $this->assertSame([
            'description' => 'Article summary for search and social cards.',
            'robots' => 'index, follow',
        ], $payload->toMetaArray());

        $this->assertSame([
            'og:title' => 'Custom OG Title',
            'og:description' => 'Custom OG Description',
            'og:type' => 'article',
            'og:url' => 'https://novaranews.com/en/news/article-title',
            'og:image' => 'https://novaranews.com/og.jpg',
        ], $payload->toOpenGraphArray(type: 'article', url: $payload->canonical));

        $this->assertSame([
            'twitter:card' => 'summary_large_image',
            'twitter:title' => 'Custom OG Title',
            'twitter:description' => 'Custom OG Description',
            'twitter:image' => 'https://novaranews.com/og.jpg',
        ], $payload->toTwitterArray());
    }

    public function test_variant_presets_resolve_expected_channel_defaults(): void
    {
        $listing = SeoMetaVariant::preset(SeoMetaVariant::LISTING);
        $article = SeoMetaVariant::preset(SeoMetaVariant::ARTICLE, 'https://novaranews.com/en/news/item');

        $this->assertTrue($listing['includeOpenGraph']);
        $this->assertTrue($listing['includeTwitter']);
        $this->assertFalse($listing['includeRobots']);

        $this->assertSame('article', $article['openGraphType']);
        $this->assertSame('https://novaranews.com/en/news/item', $article['openGraphUrl']);
        $this->assertTrue($article['includeRobots']);
    }
}

