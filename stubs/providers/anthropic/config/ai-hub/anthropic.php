<?php

return [
    'base_url' => env('AI_ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
    'api_key' => env('AI_ANTHROPIC_API_KEY', ''),
    'headers' => env('AI_ANTHROPIC_HEADERS', ''), // JSON or array
    'default_headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    'messages_path' => env('AI_ANTHROPIC_MESSAGES_PATH', '/v1/messages'),
    'timeout' => env('AI_ANTHROPIC_TIMEOUT', 60),
    'default_model' => env('AI_ANTHROPIC_DEFAULT_MODEL', 'claude-3-haiku'),
];
