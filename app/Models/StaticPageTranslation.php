<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaticPageTranslation extends Model
{
    protected $fillable = [
        'static_page_id',
        'locale',
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'canonical_url',
        'og_image_url',
        'robots_noindex',
        'robots_nofollow',
        'content',
        'admin_locked_at',
    ];

    protected function casts(): array
    {
        return [
            'admin_locked_at' => 'datetime',
            'robots_noindex' => 'boolean',
            'robots_nofollow' => 'boolean',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(StaticPage::class, 'static_page_id');
    }
}
