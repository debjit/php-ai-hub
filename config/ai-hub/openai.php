<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Environment variables are provided as sensible defaults to keep
    | 12-factor practices.
    |
    */

    'api_key'          => env('AI_OPENAI_API_KEY', env('OPENAI_API_KEY')),
    'base_url'         => rtrim(env('AI_OPENAI_BASE_URL', env('OPENAI_BASE_URL', 'https://api.openai.com/v1')), '/'),
    'model'            => env('AI_OPENAI_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini')),
    'organization'     => env('AI_OPENAI_ORG', env('OPENAI_ORG')),
    'timeout'          => (int) env('AI_OPENAI_TIMEOUT', env('OPENAI_TIMEOUT', 60)),
    /**
     * Headers can be an array via config or a JSON string via env.
     * Example ENV: AI_OPENAI_HEADERS={"X-FOO":"bar"}
     */
    'headers'          => env('AI_OPENAI_HEADERS', ''), // if string, will be decoded by resolver
    'chat_path'        => env('AI_OPENAI_CHAT_PATH', '/chat/completions'),
    'default_headers'  => [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json',
    ],

];
