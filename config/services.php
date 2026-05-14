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

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'assignment' => [
        'radius_meters' => env('ASSIGNMENT_SEARCH_RADIUS_METERS', 5000),
        'accept_timeout_seconds' => env('ASSIGNMENT_ACCEPT_TIMEOUT_SECONDS', 30),
    ],

    /*
    | OSM Nominatim (adres metni → lat/lng). user_agent: Nominatim kullanım koşulu.
    | https://operations.osmfoundation.org/policies/nominatim/
    */
    'nominatim' => [
        'enabled' => env('NOMINATIM_ENABLED', true),
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
        'user_agent' => env('NOMINATIM_USER_AGENT', 'KuryePlatform/1.0 (dev)'),
        'country_codes' => env('NOMINATIM_COUNTRY_CODES', 'tr'),
        'timeout_seconds' => (int) env('NOMINATIM_TIMEOUT', 8),
    ],

];
