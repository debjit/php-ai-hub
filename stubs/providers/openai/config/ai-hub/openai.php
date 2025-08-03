<?php

return [
    'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'api_key' => env('AI_OPENAI_API_KEY', ''),
    'headers' => env('AI_OPENAI_HEADERS', ''), // JSON or array
    'default_headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    'chat_path' => env('AI_OPENAI_CHAT_PATH', '/chat/completions'),
    'timeout' => env('AI_OPENAI_TIMEOUT', 60),
    'model' => env('AI_OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
];
