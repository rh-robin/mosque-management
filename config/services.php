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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses'      => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend'   => [
        'key' => env('RESEND_KEY'),
    ],

    'slack'    => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google'   => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

    'twitter' => [
        'api_key' => env('TWITTER_API_KEY'),
        'api_secret' => env('TWITTER_API_SECRET'),
        'access_token' => env('TWITTER_ACCESS_TOKEN'),
        'access_secret' => env('TWITTER_ACCESS_SECRET'),
        'bearer_token' => env('TWITTER_BEARER_TOKEN'),
    ],

    'openAi'   => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'claudeAi' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],
    'stripe'   => [
        'key'          => [
            'public' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
        ],
        'subscription' => [
            'medium'  => [
                'product_id' => env('STRIPE_PRODUCT_ID_MEDIUM'),
                'price_id'   => env('STRIPE_PRICE_ID_MEDIUM'),
            ],
            'premium' => [
                'product_id' => env('STRIPE_PRODUCT_ID_PREMIUM'),
                'price_id'   => env('STRIPE_PRICE_ID_PREMIUM'),
            ],
        ],
        'webhook'      => [
            'key' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],

];
