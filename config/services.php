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

    'abonnements' => [
        'orange_money' => [
            'endpoint' => env('ABONNEMENT_ORANGE_MONEY_ENDPOINT'),
            'token' => env('ABONNEMENT_ORANGE_MONEY_TOKEN'),
        ],
        'mobile_money' => [
            'endpoint' => env('ABONNEMENT_MOBILE_MONEY_ENDPOINT'),
            'token' => env('ABONNEMENT_MOBILE_MONEY_TOKEN'),
        ],
        'mobicash' => [
            'endpoint' => env('ABONNEMENT_MOBICASH_ENDPOINT'),
            'token' => env('ABONNEMENT_MOBICASH_TOKEN'),
        ],
        'wave' => [
            'endpoint' => env('ABONNEMENT_WAVE_ENDPOINT', 'https://api.wave.com/v1/checkout/sessions'),
            'token' => env('ABONNEMENT_WAVE_API_KEY', env('ABONNEMENT_WAVE_TOKEN')),
            'signing_secret' => env('ABONNEMENT_WAVE_SIGNING_SECRET'),
            'webhook_secret' => env('ABONNEMENT_WAVE_WEBHOOK_SECRET'),
            'aggregated_merchant_id' => env('ABONNEMENT_WAVE_AGGREGATED_MERCHANT_ID'),
        ],
    ],

    'assistant' => [
        'provider' => env('ASSISTANT_PROVIDER', 'gemini'),
        'timeout' => (int) env('ASSISTANT_TIMEOUT', 20),
        'max_history' => (int) env('ASSISTANT_MAX_HISTORY', 8),
        'gemini' => [
            'key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
        'groq' => [
            'key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama3-8b-8192'),
            'endpoint' => env('GROQ_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'),
        ],
        'openrouter' => [
            'key' => env('OPENROUTER_API_KEY'),
            'model' => env('OPENROUTER_MODEL', 'mistralai/mistral-7b-instruct:free'),
            'endpoint' => env('OPENROUTER_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions'),
        ],
        'ollama' => [
            'model' => env('OLLAMA_MODEL', 'llama3'),
            'endpoint' => env('OLLAMA_ENDPOINT', 'http://127.0.0.1:11434/api/chat'),
        ],
    ],

];
