<?php

namespace App\Models;

use App\Support\HtmlText;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfileTranslation extends Model
{
    protected $fillable = [
        'user_id',
        'locale',
        'title',
        'bio',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }
}
