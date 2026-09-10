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

        // Points straight at SStats by default. Production points it at the
        // Cloudflare Worker in deploy/sstats-relay-worker.js instead, because
        // that box's own network path to SStats stalls on any response past
        // ~14.6 KB — see the worker's header comment for the full diagnosis.
        'base_url' => env('SSTATS_BASE_URL', 'https://api.sstats.net'),

        // Only set when base_url is the relay: the shared secret it checks so
        // it isn't an open proxy.
        'relay_token' => env('SSTATS_RELAY_TOKEN'),
    ],

    // Where the TV listing is read from. Empty by default on purpose: which
    // guide a site may read, and on what terms, is a question about somebody's
    // terms of use rather than about code. With no URL, tv:sync does nothing.
    'tv_guide' => [
        'url' => env('TV_GUIDE_URL'),
        'timeout' => (int) env('TV_GUIDE_TIMEOUT', 30),

        // The clock the guide keeps, whatever its timestamps claim — see
        // TvGuide::startsAt(). Empty takes the stamps at their word.
        'timezone' => env('TV_GUIDE_TIMEZONE', 'Europe/Belgrade'),
    ],

    // Shared secret for POST /api/detalji-meca, where a machine that can
    // reach SStats' larger responses hands them to production, which can't.
    // Unset means the route doesn't exist.
    'detail_intake' => [
        'token' => env('DETAIL_INTAKE_TOKEN'),
    ],

    'webpush' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:podrska@utakmice.me'),
        // A goal is news while the match is on — a phone that was off for two
        // hours shouldn't hear about one from a match that's since finished.
        'ttl' => (int) env('VAPID_TTL', 1800),
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
