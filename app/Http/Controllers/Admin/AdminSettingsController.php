<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function index(): View
    {
        $botSettings  = Setting::forGroup('bot');
        $siteSettings = Setting::forGroup('site');

        return view('admin.settings.index', compact('botSettings', 'siteSettings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $group = $request->input('group', 'bot');
        $settings = Setting::forGroup($group);
        $rules = [];
        foreach ($settings as $setting) {
            $key = $setting->key;
            if (str_starts_with($key, 'default_meta_title_')) {
                $rules[$key] = ['nullable', 'string', 'max:160'];
                continue;
            }
            if (str_starts_with($key, 'default_meta_description_')) {
                $rules[$key] = ['nullable', 'string', 'max:500'];
                continue;
            }
            if (str_starts_with($key, 'category_meta_description_template_')) {
                $rules[$key] = ['nullable', 'string', 'max:500'];
                continue;
            }
            if (str_starts_with($key, 'category_intro_template_')) {
                $rules[$key] = ['nullable', 'string', 'max:500'];
                continue;
            }
            if (str_starts_with($key, 'search_meta_template_')) {
                $rules[$key] = ['nullable', 'string', 'max:500'];
                continue;
            }
            if (str_starts_with($key, 'authors_meta_template_')) {
                $rules[$key] = ['nullable', 'string', 'max:500'];
                continue;
            }
            if (str_starts_with($key, 'author_meta_template_')) {
                $rules[$key] = ['nullable', 'string', 'max:500'];
                continue;
            }
            $rules[$key] = match ($key) {
                'bot_max_tokens' => ['nullable', 'integer', 'min:2048', 'max:8192'],
                'bot_articles_per_run' => ['nullable', 'integer', 'min:1', 'max:100'],
                'articles_per_page' => ['nullable', 'integer', 'min:6', 'max:48'],
                'site_cache_ttl_home' => ['nullable', 'integer', 'min:30', 'max:86400'],
                'site_cache_ttl_nav' => ['nullable', 'integer', 'min:60', 'max:172800'],
                'editorial_guardrail_medium_threshold', 'editorial_guardrail_high_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
                'default_meta_title', 'site_name', 'site_tagline', 'publisher_name' => ['nullable', 'string', 'max:160'],
                'default_meta_description' => ['nullable', 'string', 'max:500'],
                'editorial_guardrail_generic_phrases', 'editorial_guardrail_cta_phrases' => ['nullable', 'string', 'max:3000'],
                'ga4_measurement_id' => ['nullable', 'regex:/^G\-[A-Z0-9]+$/i', 'max:32'],
                'google_site_verification' => ['nullable', 'string', 'max:255'],
                'adsense_client_id'        => ['nullable', 'regex:/^ca-pub-[0-9]+$/i', 'max:32'],
                'adsense_slot_in_article'  => ['nullable', 'regex:/^[0-9]+$/', 'max:20'],
                'adsense_slot_display'     => ['nullable', 'regex:/^[0-9]+$/', 'max:20'],
                'contact_mail_to' => ['nullable', 'email', 'max:255'],
                'default_og_image_url', 'publisher_logo_url', 'site_logo_url', 'site_favicon_url',
                'site_mark_logo_url', 'site_favicon_svg_url', 'site_favicon_ico_url', 'site_apple_touch_icon_url', 'site_apple_touch_icon_png_url' => ['nullable', 'string', 'max:500'],
                'social_x_url', 'social_facebook_url', 'social_instagram_url', 'social_youtube_url', 'social_linkedin_url' => ['nullable', 'url', 'max:500'],
                'footer_whatsapp' => ['nullable', 'string', 'max:40'],
                'footer_address' => ['nullable', 'string', 'max:500'],
                default => $setting->type === 'boolean' ? ['nullable', 'boolean'] : ['nullable', 'string', 'max:500'],
            };
        }
        $validated = $request->validate($rules);

        foreach ($settings as $setting) {
            $key = $setting->key;
            if ($setting->type === 'boolean') {
                $value = $request->boolean($key) ? '1' : '0';
            } else {
                $value = $validated[$key] ?? $setting->value;
            }
            Setting::set($key, $value);
        }

        AuditLogger::log('settings.updated', null, ['group' => $group]);

        return back()->with('success', __('site.admin_settings_saved'));
    }
}
