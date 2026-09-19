<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'mock' => env('ANTHROPIC_MOCK', false),
    ],

    'unsplash' => [
        'access_key' => env('UNSPLASH_ACCESS_KEY'),
    ],

    /*
    | Google Indexing API (optional). Service account JSON must be owned in Search Console
    | for the site. Set GOOGLE_INDEXING_ENABLED=true and credentials path to enable admin button.
    */
    'google_indexing' => [
        'enabled' => (bool) env('GOOGLE_INDEXING_ENABLED', false),
        'credentials_path' => env('GOOGLE_INDEXING_CREDENTIALS_PATH', ''),
        'daily_limit' => (int) env('GOOGLE_INDEXING_DAILY_LIMIT', 180),
        'allow_article_notifications' => (bool) env('GOOGLE_INDEXING_ALLOW_ARTICLES', false),
    ],

];
