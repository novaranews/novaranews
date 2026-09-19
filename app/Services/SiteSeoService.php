<?php

namespace App\Services;

use App\Data\SeoPayload;
use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Setting;
use App\Models\User;
use App\Support\ArticleSources;
use App\Support\HtmlText;
use App\Support\ImageDimensions;
use App\Support\SeoTemplate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

class SiteSeoService
{
    private function siteName(): string
    {
        return (string) Setting::site('site_name', config('app.name'));
    }

    public function home(string $locale): SeoPayload
    {
        $title = HtmlText::decodeEntitiesForBlade(
            SeoTemplate::localeValue('default_meta_title', $locale, (string) __('site.home_title', [], $locale))
        );
        $description = HtmlText::decodeEntitiesForBlade(
            SeoTemplate::localeValue('default_meta_description', $locale, (string) __('site.meta_description_default', [], $locale))
        );

        return SeoPayload::basic(
            title: $title,
            description: $description,
            canonical: route('home', ['locale' => $locale]),
        );
    }

    public function category(string $locale, Category $category, CategoryTranslation $translation): SeoPayload
    {
        $siteName = $this->siteName();
        $metaTransKey = 'categories.meta.'.$category->key;
        $introTransKey = 'categories.intro.'.$category->key;

        $catMetaFallback = Lang::has($metaTransKey, $locale)
            ? __($metaTransKey, ['site' => $siteName], $locale)
            : SeoTemplate::rendered('category_meta_description_template', $locale, (string) __('site.category_meta_description', [], $locale), [
                ':category' => $translation->name,
                ':site' => $siteName,
            ]);
        $catIntroFallback = Lang::has($introTransKey, $locale)
            ? __($introTransKey, [], $locale)
            : SeoTemplate::rendered('category_intro_template', $locale, (string) __('site.category_intro', [], $locale), [
                ':category' => $translation->name,
            ]);

        $description = filled($translation->meta_description ?? null)
            ? HtmlText::decodeEntitiesForBlade((string) $translation->meta_description)
            : HtmlText::decodeEntitiesForBlade($catMetaFallback);
        $intro = filled($translation->intro ?? null) ? $translation->intro : $catIntroFallback;
        $title = filled($translation->meta_title ?? null)
            ? HtmlText::decodeEntitiesForBlade((string) $translation->meta_title)
            : (HtmlText::decodeEntitiesForBlade($translation->name).' - '.HtmlText::decodeEntitiesForBlade($siteName));

        $ogTitle = filled($translation->og_title ?? null)
            ? HtmlText::decodeEntitiesForBlade((string) $translation->og_title)
            : $title;
        $ogDescription = filled($translation->og_description ?? null)
            ? HtmlText::decodeEntitiesForBlade((string) $translation->og_description)
            : $description;

        // Use the full URL (including ?page=X) so each paginated page is self-canonical.
        // This prevents "Canonicals: Non-Indexable" errors where page=2 canonicalized
        // to the base URL (url()->current() strips query params).
        // Admin-set canonical_url takes precedence on page 1 only.
        $canonicalUrl = filled($translation->canonical_url ?? null)
            ? $translation->canonical_url
            : request()->fullUrl();

        return SeoPayload::forCategory(
            title: $title,
            description: $description,
            canonical: $canonicalUrl,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageUrl: filled($translation->og_image_url ?? null) ? $translation->og_image_url : null,
            robots: collect([
                ($translation->robots_noindex ?? false) ? 'noindex' : 'index',
                ($translation->robots_nofollow ?? false) ? 'nofollow' : 'follow',
            ])->implode(', '),
            intro: $intro,
        );
    }

    public function search(string $locale, string $query, ?string $canonical = null): SeoPayload
    {
        $siteName = $this->siteName();
        $canonicalUrl = $canonical ?? route('search', ['locale' => $locale, 'q' => $query]);
        $defaultOg = Setting::site('default_og_image_url', config('novaranews.default_og_image_url'));

        return SeoPayload::basic(
            title: HtmlText::decodeEntitiesForBlade(__('site.search_title', ['query' => $query], $locale).' - '.$siteName),
            description: HtmlText::decodeEntitiesForBlade(SeoTemplate::rendered('search_meta_template', $locale, (string) __('site.search_meta', [], $locale), [
                ':query' => $query,
                ':site' => $siteName,
            ])),
            canonical: $canonicalUrl,
            ogImageUrl: $defaultOg ?: null,
        );
    }

    public function authors(string $locale, ?string $canonical = null): SeoPayload
    {
        $siteName = $this->siteName();
        $canonicalUrl = $canonical ?? route('authors.index', ['locale' => $locale]);
        $defaultOg = Setting::site('default_og_image_url', config('novaranews.default_og_image_url'));

        return SeoPayload::basic(
            title: HtmlText::decodeEntitiesForBlade(__('site.authors_title', [], $locale).' - '.$siteName),
            description: HtmlText::decodeEntitiesForBlade(SeoTemplate::rendered('authors_meta_template', $locale, (string) __('site.authors_meta', [], $locale), [
                ':site' => $siteName,
            ])),
            canonical: $canonicalUrl,
            ogImageUrl: $defaultOg ?: null,
        );
    }

    public function author(string $locale, User $author, ?string $canonical = null): SeoPayload
    {
        $siteName = $this->siteName();
        $jobTitle = $author->profileTitleForLocale($locale) ?? '';
        $fallback = HtmlText::decodeEntitiesForBlade(SeoTemplate::rendered('author_meta_template', $locale, ':name - :site', [
            ':name' => $author->name,
            ':title' => $jobTitle,
            ':site' => $siteName,
        ]));
        $bio = $author->profileBioForLocale($locale);
        $description = $bio
            ? Str::limit(
                strip_tags(HtmlText::decodeEntitiesForBlade($bio)),
                160,
                ''
            )
            : $fallback;
        $canonicalUrl = $canonical ?? route('author.show', ['locale' => $locale, 'slug' => $author->slug]);
        $defaultOg = Setting::site('default_og_image_url', config('novaranews.default_og_image_url'));
        $ogImageUrl = $author->avatar
            ? url(Storage::url($author->avatar))
            : ($defaultOg ?: null);

        return SeoPayload::basic(
            title: HtmlText::decodeEntitiesForBlade(($author->name).($jobTitle !== '' ? ' - '.$jobTitle : '').' | '.$siteName),
            description: $description,
            canonical: $canonicalUrl,
            ogTitle: HtmlText::decodeEntitiesForBlade($author->name.' | '.$siteName),
            ogImageUrl: $ogImageUrl,
        );
    }

    public function staticPage(array $content): SeoPayload
    {
        return SeoPayload::forStaticPage(
            title: HtmlText::decodeEntitiesForBlade((string) ($content['meta_title'] ?? '')),
            description: HtmlText::decodeEntitiesForBlade((string) ($content['meta_description'] ?? '')),
            canonical: (string) ($content['canonical_url'] ?? url()->current()),
            ogTitle: HtmlText::decodeEntitiesForBlade((string) ($content['og_title'] ?? '')),
            ogDescription: HtmlText::decodeEntitiesForBlade((string) ($content['og_description'] ?? '')),
            ogImageUrl: $content['og_image_url'] ?? null,
            robots: (($content['robots_noindex'] ?? false) ? 'noindex' : 'index').', '.(($content['robots_nofollow'] ?? false) ? 'nofollow' : 'follow'),
        );
    }

    public function article(Article $article, ArticleTranslation $translation, string $locale, bool $preview = false): SeoPayload
    {
        $titleRaw = HtmlText::decodeEntitiesForBlade((string) ($translation->meta_title ?: $translation->title));
        $title = $titleRaw; // exactly what admin entered, no suffix
        $descRaw = $translation->meta_description ?: $translation->excerpt;
        if (! $descRaw) {
            $descRaw = Str::limit(strip_tags((string) $translation->body), 160, '');
        }
        $description = $descRaw
            ? strip_tags(HtmlText::decodeEntitiesForBlade((string) $descRaw))
            : '';
        $canonical = $translation->canonical_url ?: ($article->publicUrl($locale) ?? url()->current());
        $robots = $preview
            ? 'noindex, nofollow'
            : (($translation->robots_noindex ? 'noindex' : 'index').', '.($translation->robots_nofollow ? 'nofollow' : 'follow'));

        $ogTitle = HtmlText::decodeEntitiesForBlade((string) ($translation->og_title ?: ($translation->meta_title ?: $translation->title)));
        $ogDescription = $translation->og_description
            ? Str::limit(strip_tags(HtmlText::decodeEntitiesForBlade((string) $translation->og_description)), 160, '')
            : $description;
        $ogImage = $translation->og_image_url ?: ($article->featured_image ? url(Storage::url($article->featured_image)) : null);
        if (! $ogImage) {
            $ogImage = Setting::site('default_og_image_url', config('novaranews.default_og_image_url'));
        }

        $featuredDims = $article->featured_image ? ImageDimensions::forStoragePublic($article->featured_image) : null;
        $catTr = $article->category?->translate($locale);
        $publisher = [
            '@type' => 'Organization',
            'name' => HtmlText::decodeEntitiesForBlade((string) Setting::site('publisher_name', $this->siteName())),
            'url'  => url('/'),
        ];
        if ($logo = Setting::site('publisher_logo_url', Setting::site('site_logo_url', config('novaranews.publisher_logo_url')))) {
            $logoData = ['@type' => 'ImageObject', 'url' => $logo];
            // Try to detect dimensions for local public files
            $localPath = public_path(ltrim(parse_url($logo, PHP_URL_PATH) ?? '', '/'));
            if (is_file($localPath) && ($dims = @getimagesize($localPath)) !== false) {
                $logoData['width']  = $dims[0];
                $logoData['height'] = $dims[1];
            }
            $publisher['logo'] = $logoData;
        }

        if ($article->author) {
            $authorSchema = ['@type' => 'Person', 'name' => $article->author->name];
            $authorUrl = $article->author->profileUrl();
            if ($authorUrl) {
                $authorSchema['url'] = $authorUrl;
            }
            $authorJobTitle = $article->author->profileTitleForLocale($locale);
            if ($authorJobTitle) {
                $authorSchema['jobTitle'] = $authorJobTitle;
            }
            if ($article->author->mailtoPublicEmail()) {
                $authorSchema['email'] = $article->author->mailtoPublicEmail();
            }
            if ($article->author->show_phone && $article->author->phone) {
                $authorSchema['telephone'] = $article->author->phone;
            }
            $authorSameAs = $article->author->publicSameAs();
            if ($authorSameAs !== []) {
                $authorSchema['sameAs'] = $authorSameAs;
            }
        } else {
            // Fallback to publisher Organization when no author is assigned
            $authorSchema = $publisher;
        }

        $schemaOrgArticleType = match ($article->contentTypeKey()) {
            'news' => 'NewsArticle',
            default => 'Article',
        };

        $articleLd = [
            '@context' => 'https://schema.org',
            '@type' => $schemaOrgArticleType,
            'headline' => HtmlText::decodeEntitiesForBlade((string) $translation->title),
            'isAccessibleForFree' => true,
            'dateModified' => $article->publicModifiedAt($translation)->toIso8601String(),
            'inLanguage' => $locale,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonical,
            ],
            'url' => $canonical,
            'publisher' => $publisher,
        ];
        if ($authorSchema) {
            $articleLd['author'] = $authorSchema;
        }
        if ($catTr) {
            $articleLd['articleSection'] = HtmlText::decodeEntitiesForBlade($catTr->name);
        }
        if ($description !== '') {
            $articleLd['description'] = $description;
        }
        if ($article->published_at) {
            $articleLd['datePublished'] = $article->published_at->toIso8601String();
        }
        if ($ogImage) {
            $imgObj = array_filter([
                '@type'  => 'ImageObject',
                'url'    => $ogImage,
                'width'  => $featuredDims['width'] ?? null,
                'height' => $featuredDims['height'] ?? null,
            ], fn ($v) => $v !== null);
            $articleLd['image'] = [$imgObj];
        }
        $sourceUrls = ArticleSources::urls($translation->sources ?? []);
        if (! empty($sourceUrls)) {
            $articleLd['citation'] = $sourceUrls;
        }

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => __('site.home_breadcrumb', [], $locale),
                    'item' => route('home', ['locale' => $locale]),
                ],
            ],
        ];
        if ($catTr) {
            $breadcrumb['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => HtmlText::decodeEntitiesForBlade($catTr->name),
                'item' => route('category.show', ['locale' => $locale, 'categoryPrefix' => category_path_segment($locale), 'slug' => $catTr->slug]),
            ];
            $breadcrumb['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => HtmlText::decodeEntitiesForBlade((string) $translation->title),
                'item' => $canonical,
            ];
        } else {
            $breadcrumb['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => HtmlText::decodeEntitiesForBlade((string) $translation->title),
                'item' => $canonical,
            ];
        }

        return SeoPayload::forArticle(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageUrl: $ogImage,
            robots: $robots,
            extra: [
                'featuredDims' => $featuredDims,
                'articleSection' => $catTr?->name,
                'authorProfileUrl' => $article->author?->profileUrl(),
            ],
            schemas: [
                'article' => $articleLd,
                'breadcrumb' => $breadcrumb,
            ]
        );
    }
}
