<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\ArticleContentRules;
use App\Support\HtmlText;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Stevebauman\Purify\Facades\Purify;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'public_email',
        'show_public_email',
        'password',
        'is_admin',
        'slug',
        'title',
        'bio',
        'avatar',
        'twitter',
        'linkedin',
        'phone',
        'show_phone',
        'whatsapp',
        'show_whatsapp',
        'telegram',
        'show_telegram',
        'address',
        'show_address',
        'admin_locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'show_public_email' => 'boolean',
            'show_phone' => 'boolean',
            'show_whatsapp' => 'boolean',
            'show_telegram' => 'boolean',
            'show_address' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null || $value === '' ? $value : HtmlText::decodeEntitiesForBlade($value),
        );
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'user_id');
    }

    public function profileTranslations(): HasMany
    {
        return $this->hasMany(UserProfileTranslation::class);
    }

    /**
     * Author job title for public pages (locale-specific, with sensible fallbacks).
     */
    public function profileTitleForLocale(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $default = config('novaranews.default_locale', 'en');

        $row = $this->resolveProfileTranslationRow($locale);
        if ($row && filled($row->title)) {
            return $row->title;
        }
        if ($locale !== $default) {
            $fallback = $this->resolveProfileTranslationRow($default);
            if ($fallback && filled($fallback->title)) {
                return $fallback->title;
            }
        }

        return filled($this->title) ? $this->title : null;
    }

    /**
     * Author biography for public pages (locale-specific, with sensible fallbacks).
     */
    public function profileBioForLocale(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $default = config('novaranews.default_locale', 'en');

        $row = $this->resolveProfileTranslationRow($locale);
        if ($row && filled($row->bio)) {
            return $row->bio;
        }
        if ($locale !== $default) {
            $fallback = $this->resolveProfileTranslationRow($default);
            if ($fallback && filled($fallback->bio)) {
                return $fallback->bio;
            }
        }

        return filled($this->bio) ? $this->bio : null;
    }

    /**
     * Sanitized biography HTML for public pages (safe for unescaped Blade output).
     */
    public function profileBioHtmlForLocale(?string $locale = null): ?string
    {
        $raw = $this->profileBioForLocale($locale);
        if ($raw === null || $raw === '') {
            return null;
        }

        return Purify::clean($raw);
    }

    /** Bio word count for readiness checks (default-locale profile). */
    public function profileBioWordCountForDefaultLocale(): int
    {
        $default = config('novaranews.default_locale', 'en');
        $bio = $this->profileBioForLocale($default) ?? '';

        return ArticleContentRules::wordCount((string) $bio);
    }

    private function resolveProfileTranslationRow(string $locale): ?UserProfileTranslation
    {
        if ($this->relationLoaded('profileTranslations')) {
            return $this->profileTranslations->firstWhere('locale', $locale);
        }

        return $this->profileTranslations()->where('locale', $locale)->first();
    }

    /**
     * Avatar URL — stored file or Gravatar fallback (mystery-person silhouette).
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }
        $hash = md5(strtolower(trim((string) $this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?s=400&d=mp";
    }

    /**
     * Public author profile URL for the given locale.
     */
    public function profileUrl(?string $locale = null): ?string
    {
        if (! $this->slug) {
            return null;
        }
        $locale ??= app()->getLocale();
        return route('author.show', ['locale' => $locale, 'slug' => $this->slug]);
    }

    public function twitterUrl(): ?string
    {
        $handle = $this->twitterHandle();
        if ($handle === null) {
            return null;
        }

        return 'https://x.com/'.$handle;
    }

    public function twitterHandle(): ?string
    {
        if (! is_string($this->twitter) || trim($this->twitter) === '') {
            return null;
        }

        $handle = ltrim(trim($this->twitter), '@');

        return $handle !== '' ? $handle : null;
    }

    public function linkedInUrl(): ?string
    {
        if (! is_string($this->linkedin) || trim($this->linkedin) === '') {
            return null;
        }

        return 'https://www.linkedin.com/in/'.trim($this->linkedin);
    }

    public function telegramUrl(): ?string
    {
        if (! $this->show_telegram || ! is_string($this->telegram) || trim($this->telegram) === '') {
            return null;
        }

        $handle = ltrim(trim($this->telegram), '@');

        return $handle !== '' ? 'https://t.me/'.$handle : null;
    }

    /** Username for public chips (without leading @). */
    public function telegramDisplayHandle(): ?string
    {
        if (! is_string($this->telegram) || trim($this->telegram) === '') {
            return null;
        }
        $handle = ltrim(trim($this->telegram), '@');

        return $handle !== '' ? $handle : null;
    }

    public function whatsappUrl(): ?string
    {
        if (! $this->show_whatsapp || ! is_string($this->whatsapp) || trim($this->whatsapp) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp);
        if (! is_string($digits) || strlen($digits) < 8) {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }

    /** Digits or formatted number as stored, for button label. */
    public function whatsappDisplayNumber(): ?string
    {
        if (! is_string($this->whatsapp) || trim($this->whatsapp) === '') {
            return null;
        }

        return trim($this->whatsapp);
    }

    public function telUrl(): ?string
    {
        if (! $this->show_phone || ! is_string($this->phone) || trim($this->phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $this->phone);
        if (! is_string($digits) || strlen($digits) < 6) {
            return null;
        }

        return 'tel:'.$digits;
    }

    public function mailtoPublicEmail(): ?string
    {
        if (! $this->show_public_email || ! is_string($this->public_email) || trim($this->public_email) === '') {
            return null;
        }

        return 'mailto:'.trim($this->public_email);
    }

    public function publicSameAs(): array
    {
        return array_values(array_filter([
            $this->twitterUrl(),
            $this->linkedInUrl(),
            $this->telegramUrl(),
            $this->whatsappUrl(),
        ]));
    }

    public function hasPublicContact(): bool
    {
        return $this->mailtoPublicEmail() !== null
            || $this->telUrl() !== null
            || ($this->show_address && filled($this->address))
            || $this->publicSameAs() !== [];
    }
}
