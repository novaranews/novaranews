<?php

namespace App\Models;

use App\Support\HtmlText;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleTranslation extends Model
{
    protected $fillable = [
        'article_id',
        'locale',
        'title',
        'slug',
        'excerpt',
        'body',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'canonical_url',
        'og_image_url',
        'robots_noindex',
        'robots_nofollow',
        'sources',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'robots_noindex' => 'boolean',
            'robots_nofollow' => 'boolean',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** Plain-text / excerpt / SEO: avoid showing literal &amp; when DB stored pre-escaped entities. Body is HTML — do not decode. */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }

    protected function excerpt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }

    protected function ogTitle(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }

    protected function ogDescription(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }
}
