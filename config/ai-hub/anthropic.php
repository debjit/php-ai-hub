<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Environment variables are provided as sensible defaults to keep
    | 12-factor practices.
    |
    */

    'api_key'         => env('AI_ANTHROPIC_API_KEY', env('ANTHROPIC_API_KEY')),
    'base_url'        => rtrim(env('AI_ANTHROPIC_BASE_URL', env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com')), '/'),
    'model'           => env('AI_ANTHROPIC_MODEL', env('ANTHROPIC_MODEL', 'claude-3-5-sonnet')),
    'timeout'         => (int) env('AI_ANTHROPIC_TIMEOUT', env('ANTHROPIC_TIMEOUT', 60)),
    'headers'         => env('AI_ANTHROPIC_HEADERS', ''),
    'messages_path'   => env('AI_ANTHROPIC_MESSAGES_PATH', '/v1/messages'),
    'default_headers' => [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json',
    ],

];
