<?php

declare(strict_types=1);

namespace App\Services\AI\Providers\OpenAI;

use App\Services\AI\Providers\OpenAI\Connectors\HttpConnector;

/**
 * Minimal chat client for OpenAI-compatible APIs.
 * - Reads base URL, API key, and custom headers from env/config via HttpConnector::config()
 * - Sends POST {base_url}{chat_path} with JSON payload
 * - Supports Bearer token and arbitrary custom headers
 */
final class ChatClient
{
    public function __construct(private readonly HttpConnector $http)
    {
    }

    /**
     * Perform a chat completion request.
     *
     * @param array $messages   Array of message objects, e.g. [['role' => 'user', 'content' => 'Hello']]
     * @param array $options    Optional overrides: ['model' => 'gpt-4o-mini', 'base_url' => '...', 'api_key' => '...', 'headers' => [...], 'timeout' => 60, 'chat_path' => '/chat/completions']
     * @return array            Structured response: ['status' => int, 'headers' => array, 'body' => array|null, 'raw' => string, 'error' => string|null]
     */
    public function chat(array $messages, array $options = []): array
    {
        $cfg = $this->http->config($options);
        $headers = $this->http->buildHeaders($cfg);

        // Build payload with sane defaults; allow user overrides to merge in
        $payload = array_merge([
            'model' => $options['model'] ?? ($options['default_model'] ?? 'gpt-4o-mini'),
            'messages' => $messages,
            // Common OpenAI options - caller can override via $options['payload'] if needed
            'temperature' => 0.7,
            'stream' => false,
        ], $options['payload'] ?? []);

        $url = $cfg['base_url'] . ($options['chat_path'] ?? $cfg['chat_path']);

        $result = $this->http->postJson($url, $headers, $payload, (int) ($cfg['timeout'] ?? 60));

        // Normalize error field
        if (!isset($result['error'])) {
            $result['error'] = null;
        }

        return $result;
    }
}
