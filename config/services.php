<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'sstats' => [
        'key' => env('SSTATS_API_KEY'),
        'base_url' => env('SSTATS_BASE_URL', 'https://api.sstats.net'),
    ],

    'youtube' => [
        // No key needed — the channel feed and oEmbed are both public and
        // free. Set YOUTUBE_API_KEY to also learn whether a video is live
        // (oEmbed can only say whether it's embeddable).
        'key' => env('YOUTUBE_API_KEY'),
        'timeout' => (int) env('YOUTUBE_TIMEOUT', 10),
        'oembed_cap' => (int) env('YOUTUBE_OEMBED_CAP', 40),
    ],

];
