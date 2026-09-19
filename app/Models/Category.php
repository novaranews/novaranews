<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Model
{
    protected $fillable = ['key', 'sort_order'];

    /**
     * Sıralı kategori anahtarları (bot, RSS, doğrulama kuralları).
     */
    public static function orderedKeys(): Collection
    {
        return static::query()->orderBy('sort_order')->pluck('key');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function translate(?string $locale = null): ?CategoryTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale);
    }
}
