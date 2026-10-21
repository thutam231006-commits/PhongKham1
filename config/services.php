<?php

return [
    'google_meet' => [
        'url' => env('GOOGLE_MEET_URL', 'https://meet.google.com/new'),
    ],

    'sepay' => [
        'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
        'webhook_api_key' => env('SEPAY_WEBHOOK_API_KEY', env('SEPAY_API_KEY')),
        'api_key' => env('SEPAY_API_KEY'),
        'bank_account' => env('SEPAY_BANK_ACCOUNT'),
        'bank_code' => env('SEPAY_BANK_CODE', 'VietinBank'),
        'account_name' => env('SEPAY_ACCOUNT_NAME'),
        'qr_base_url' => env('SEPAY_QR_BASE_URL', 'https://vietqr.app/img'),
    ],

    'vietqr' => [
        'bank_id' => env('VIETQR_BANK_ID', env('SEPAY_BANK_CODE', 'VietinBank')),
        'account_number' => env('VIETQR_ACCOUNT_NUMBER', env('SEPAY_BANK_ACCOUNT')),
        'account_name' => env('VIETQR_ACCOUNT_NAME', env('SEPAY_ACCOUNT_NAME')),
    ],

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

];
