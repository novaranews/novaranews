<?php

namespace App\Models;

use App\Support\HtmlText;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryTranslation extends Model
{
    protected $fillable = [
        'category_id',
        'locale',
        'name',
        'slug',
        'intro',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'canonical_url',
        'og_image_url',
        'robots_noindex',
        'robots_nofollow',
    ];

    protected function casts(): array
    {
        return [
            'robots_noindex' => 'boolean',
            'robots_nofollow' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Plain-text fields: DB may store HTML entities from imports or old admin saves. */
    protected function name(): Attribute
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
