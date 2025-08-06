<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Centralized OpenAI-compatible Providers
    |--------------------------------------------------------------------------
    |
    | Declare multiple providers that are OpenAI API compatible. All of them
    | can reuse the same OpenAIProvider class as a drop-in. Users typically
    | only need to set the API key via environment variables.
    |
    | You can switch provider via:
    |   AI_HUB_DRIVER=groq
    |   AI_HUB_DRIVER=openrouter
    |   AI_HUB_DRIVER=openai
    |
    | Each provider supports overriding any field via env. Examples:
    |   AI_OPENAI_API_KEY=sk-...
    |   AI_GROQ_API_KEY=gsk_...
    |   AI_OPENROUTER_API_KEY=sk-or-v1-...
    |   AI_GROQ_BASE_URL=https://api.groq.com/openai/v1
    |   AI_OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
    |   AI_GROQ_MODEL=llama3-8b-8192
    |   AI_OPENROUTER_MODEL=openrouter/horizon-beta
    |
    */

    'openai' => [
        'api_key'         => env('AI_OPENAI_API_KEY', env('OPENAI_API_KEY')),
        'base_url'        => rtrim(env('AI_OPENAI_BASE_URL', env('OPENAI_BASE_URL', 'https://api.openai.com/v1')), '/'),
        'model'           => env('AI_OPENAI_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini')),
        'organization'    => env('AI_OPENAI_ORG', env('OPENAI_ORG')),
        'timeout'         => (int) env('AI_OPENAI_TIMEOUT', env('OPENAI_TIMEOUT', 60)),
        // Headers can be an array via config or a JSON string via env, e.g. AI_OPENAI_HEADERS={"X-FOO":"bar"}
        'headers'         => env('AI_OPENAI_HEADERS', ''),
        'chat_path'       => env('AI_OPENAI_CHAT_PATH', '/chat/completions'),
        'default_headers' => [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ],
        // All OpenAI-compatible providers can reuse the same class
        'provider_class'  => \App\AIHub\OpenAIProvider::class,
    ],

    'groq' => [
        'api_key'         => env('AI_GROQ_API_KEY'),
        // Groq provides an OpenAI-compatible endpoint
        'base_url'        => rtrim(env('AI_GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),
        'model'           => env('AI_GROQ_MODEL', 'llama3-8b-8192'),
        'timeout'         => (int) env('AI_GROQ_TIMEOUT', 60),
        'headers'         => env('AI_GROQ_HEADERS', ''),
        'chat_path'       => env('AI_GROQ_CHAT_PATH', '/chat/completions'),
        'default_headers' => [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'provider_class'  => \App\AIHub\OpenAIProvider::class,
    ],

    'openrouter' => [
        'api_key'         => env('AI_OPENROUTER_API_KEY'),
        // OpenRouter exposes OpenAI-compatible API
        'base_url'        => rtrim(env('AI_OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'), '/'),
        'model'           => env('AI_OPENROUTER_MODEL', 'openrouter/horizon-beta'),
        'timeout'         => (int) env('AI_OPENROUTER_TIMEOUT', 60),
        // OpenRouter supports extra headers; allow JSON via env if needed.
        // Example: AI_OPENROUTER_HEADERS={"HTTP-Referer":"https://your.app","X-Title":"Your App"}
        'headers'         => env('AI_OPENROUTER_HEADERS', ''),
        'chat_path'       => env('AI_OPENROUTER_CHAT_PATH', '/chat/completions'),
        'default_headers' => [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'provider_class'  => \App\AIHub\OpenAIProvider::class,
    ],

];
