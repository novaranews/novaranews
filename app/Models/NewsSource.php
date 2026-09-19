<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsSource extends Model
{
    protected $fillable = [
        'name',
        'url',
        'locale',
        'category_key',
        'is_active',
        'last_fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiArticleGeneration::class);
    }
}
