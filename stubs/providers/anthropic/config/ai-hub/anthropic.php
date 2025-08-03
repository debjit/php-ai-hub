<?php

return [
    'base_url' => env('AI_ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
    'api_key' => env('AI_ANTHROPIC_API_KEY', ''),
    'headers' => env('AI_ANTHROPIC_HEADERS', ''), // JSON or array
    'default_headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'x-api-key' => env('AI_ANTHROPIC_API_KEY', ''),
        'anthropic-version' => '2023-06-01',
    ],
    'chat_path' => env('AI_ANTHROPIC_CHAT_PATH', '/messages'),
    'timeout' => env('AI_ANTHROPIC_TIMEOUT', 60),
    'model' => env('AI_ANTHROPIC_DEFAULT_MODEL', 'claude-3-haiku-20240307'),
];
