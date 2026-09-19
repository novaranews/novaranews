<?php

namespace App\Models;

use App\Support\HtmlText;
use App\Support\SafeCache;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        if (! array_key_exists($key, $all)) {
            return is_string($default) && $default !== ''
                ? HtmlText::decodeEntitiesForBlade($default)
                : $default;
        }

        return static::cast($all[$key]['value'], $all[$key]['type']);
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->where('key', $key)->update(['value' => (string) $value]);
        SafeCache::forget('site_settings');
    }

    /** @return array<string, array{value: string|null, type: string}> */
    public static function allCached(): array
    {
        return SafeCache::remember('site_settings', 3600, function () {
            return static::query()
                ->get(['key', 'value', 'type'])
                ->keyBy('key')
                ->map(fn ($s) => ['value' => $s->value, 'type' => $s->type])
                ->toArray();
        });
    }

    public static function forGroup(string $group): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()->where('group', $group)->orderBy('id')->get();
    }

    public static function flush(): void
    {
        SafeCache::forget('site_settings');
    }

    public static function site(string $key, mixed $fallback = null): mixed
    {
        return static::get($key, $fallback);
    }

    /**
     * Footer social profile links: valid https URL + "show on site" enabled.
     *
     * @return array<string, string> keyed by network id (x, facebook, …)
     */
    public static function publicSocialFooterLinks(): array
    {
        $networks = [
            'x' => [
                'url' => 'social_x_url',
                'visible' => 'social_x_visible',
                'config' => 'x',
            ],
            'facebook' => [
                'url' => 'social_facebook_url',
                'visible' => 'social_facebook_visible',
                'config' => 'facebook',
            ],
            'instagram' => [
                'url' => 'social_instagram_url',
                'visible' => 'social_instagram_visible',
                'config' => 'instagram',
            ],
            'youtube' => [
                'url' => 'social_youtube_url',
                'visible' => 'social_youtube_visible',
                'config' => 'youtube',
            ],
            'linkedin' => [
                'url' => 'social_linkedin_url',
                'visible' => 'social_linkedin_visible',
                'config' => 'linkedin',
            ],
        ];

        $links = [];
        foreach ($networks as $key => $cfg) {
            $fallback = config('novaranews.social_links.'.$cfg['config']);
            $url = static::site($cfg['url'], $fallback ?? '');
            $visible = (bool) static::site($cfg['visible'], true);
            if (! $visible || ! is_string($url) || ! str_starts_with($url, 'http')) {
                continue;
            }
            $links[$key] = $url;
        }

        return $links;
    }

    private static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json'    => json_decode((string) $value, true),
            default   => is_string($value) && $value !== ''
                ? HtmlText::decodeEntitiesForBlade($value)
                : $value,
        };
    }
}
