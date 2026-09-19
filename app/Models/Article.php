<?php

namespace App\Models;

use App\Services\ImageOptimizerService;
use App\Support\HtmlText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Article extends Model
{
    public const CONTENT_TYPES = ['news', 'analysis', 'guide', 'review'];

    protected $fillable = [
        'category_id',
        'user_id',
        'locale',
        'status',
        'published_at',
        'featured_image',
        'featured_image_alt',
        'featured_image_caption',
        'is_breaking',
        'is_editors_pick',
        'content_type',
        'is_ai_generated',
        'editor_reviewed_at',
        'hreflang_group',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_breaking' => 'boolean',
            'is_editors_pick' => 'boolean',
            'is_ai_generated' => 'boolean',
            'editor_reviewed_at' => 'datetime',
            'google_indexing_notified_at' => 'datetime',
        ];
    }

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Returns the thumbnail storage path if it exists, null otherwise.
     * Thumbnails are generated at upload/optimize time and stored at
     * media/thumbs/<filename>.webp — used for srcset in article cards.
     */
    protected function featuredImageThumb(): Attribute
    {
        return Attribute::make(
            get: function () {
                $path = $this->getRawOriginal('featured_image');
                if (! $path) {
                    return null;
                }

                $dir  = dirname($path);
                $base = pathinfo($path, PATHINFO_FILENAME);
                $thumbDir = ($dir === '.' || $dir === '') ? ImageOptimizerService::THUMB_SUBDIR : $dir.'/'.ImageOptimizerService::THUMB_SUBDIR;
                $thumbPath = $thumbDir.'/'.$base.'.webp';

                return Storage::disk('public')->exists($thumbPath) ? $thumbPath : null;
            }
        );
    }

    protected function featuredImageAlt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }

    protected function featuredImageCaption(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ArticleTranslation::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class);
    }

    public function translate(?string $locale = null): ?ArticleTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale);
    }

    public function publicUrl(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        if ((string) $this->locale !== $locale) {
            return null;
        }

        $this->loadMissing(['translations']);
        $tr = $this->translations->firstWhere('locale', $locale);
        if (! $tr || ! filled($tr->slug)) {
            return null;
        }

        return route('article.show', [
            'locale' => $locale,
            'articlePrefix' => article_path_segment($locale, $this->contentTypeKey()),
            'articleSlug' => $tr->slug,
        ]);
    }

    public function urlForLocale(string $locale): string
    {
        return $this->publicUrl($locale) ?? '#';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublishedLive(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * Latest change on the article row or any translation (for Indexing API button state).
     */
    public function publicModifiedAt(?ArticleTranslation $translation = null): Carbon
    {
        $max = $this->updated_at ?? $this->created_at ?? now();

        if ($this->published_at !== null && $this->published_at->gt($max)) {
            $max = $this->published_at;
        }

        $translationTouchedAt = $translation?->updated_at ?? $translation?->created_at;
        if ($translationTouchedAt !== null && $translationTouchedAt->gt($max)) {
            $max = $translationTouchedAt;
        }

        return $max;
    }

    public function googleIndexingContentTouchedAt(): Carbon
    {
        $this->loadMissing('translations');
        $max = $this->publicModifiedAt();
        foreach ($this->translations as $tr) {
            $tu = $tr->updated_at ?? $tr->created_at;
            if ($tu !== null && $tu->gt($max)) {
                $max = $tu;
            }
        }

        return $max;
    }

    public function googleIndexingEligible(): bool
    {
        if (! (bool) config('services.google_indexing.allow_article_notifications', false)) {
            return false;
        }

        if (! $this->isPublishedLive()) {
            return false;
        }
        $this->loadMissing('translations');
        $tr = $this->translations->firstWhere('locale', $this->locale) ?? $this->translations->first();
        if (! $tr || ! filled($tr->slug) || ($tr->robots_noindex ?? false)) {
            return false;
        }

        return $this->publicUrl($this->locale) !== null;
    }

    public function googleIndexingNeedsNotify(): bool
    {
        if ($this->google_indexing_notified_at === null) {
            return true;
        }

        return $this->googleIndexingContentTouchedAt()->gt($this->google_indexing_notified_at);
    }

    public function contentTypeKey(): string
    {
        $k = $this->content_type ?? 'news';

        return in_array($k, self::CONTENT_TYPES, true) ? $k : 'news';
    }

    /**
     * Tailwind classes for the content-type pill (used in listings and article header).
     */
    public function contentTypeBadgeClasses(): string
    {
        return match ($this->contentTypeKey()) {
            'analysis' => 'border-violet-300 bg-violet-50 text-violet-900 dark:border-violet-500/50 dark:bg-violet-950/50 dark:text-violet-200',
            'guide' => 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-500/50 dark:bg-emerald-950/50 dark:text-emerald-200',
            'review' => 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500/50 dark:bg-amber-950/50 dark:text-amber-200',
            default => 'border-stone-200 bg-stone-100/90 text-stone-700 dark:border-stone-600 dark:bg-stone-800/90 dark:text-stone-300',
        };
    }
}
