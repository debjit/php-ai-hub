<?php

return [
    // Strict, non-legacy OpenAI config. Only AI_* envs, no OPENAI_* fallbacks.
    'api_key'         => env('AI_OPENAI_API_KEY'),
    'base_url'        => rtrim(env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
    'model'           => env('AI_OPENAI_MODEL', 'gpt-4o-mini'),
    // Organization is not used anywhere; removing legacy support and key entirely.

    'timeout'         => (int) env('AI_OPENAI_TIMEOUT', 60),

    // Headers can be an array via config or a JSON string via env, decoded by resolver.
    'headers'         => env('AI_OPENAI_HEADERS', ''),

    // OpenAI-compatible chat endpoint path.
    'chat_path'       => env('AI_OPENAI_CHAT_PATH', '/chat/completions'),

    // Defaults merged with any provided headers.
    'default_headers' => [
        'Accept' => 'application/json',
    ],
];
