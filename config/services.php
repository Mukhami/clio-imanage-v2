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

    'clio' => [
        'key'          => env('CLIO_APP_KEY'),
        'secret'       => env('CLIO_APP_SECRET'),
        'redirect_uri' => env('CLIO_REDIRECT_URI', 'http://localhost:8000/oauth/clio/callback'),
    ],

    'imanage' => [
        'api_url'      => env('IMANAGE_API_URL'),
        'app_key'      => env('IMANAGE_APP_KEY'),
        'app_secret'   => env('IMANAGE_APP_SECRET'),
        'redirect_uri' => env('IMANAGE_REDIRECT_URI', 'http://localhost:8000/oauth/imanage/callback'),
    ],

];
