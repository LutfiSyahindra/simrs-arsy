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

    'go_wa' => [
        'base_url' => env('WA_GATEWAY_URL', env('GO_WA_BASE_URL', 'http://127.0.0.1:3000')),
        'token' => env('WA_GATEWAY_TOKEN', env('GO_WA_TOKEN', env('API_TOKEN'))),
        'timeout' => env('WA_GATEWAY_TIMEOUT', env('GO_WA_TIMEOUT', 60)),
        'queue_delay_seconds' => env('WA_GATEWAY_QUEUE_DELAY', 8),
    ],

];
