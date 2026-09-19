<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiArticleGeneration extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'news_source_id',
        'user_id',
        'article_id',
        'category_id',
        'source_title',
        'source_url',
        'source_guid',
        'source_locale',
        'source_text',
        'source_packets',
        'target_locales',
        'model',
        'status',
        'error_message',
        'raw_response',
        'input_tokens',
        'output_tokens',
        'estimated_cost_usd',
        'image_url',
        'image_alt',
        'approved_by_user_id',
        'approval_note',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'source_packets' => 'array',
            'target_locales' => 'array',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'estimated_cost_usd' => 'decimal:6',
            'approved_at' => 'datetime',
        ];
    }

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DONE = 'done';
    const STATUS_FAILED = 'failed';

    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
