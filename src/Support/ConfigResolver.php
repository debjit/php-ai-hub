<?php

declare(strict_types=1);

namespace App\AIHub\Support;

/**
 * ConfigResolver centralizes configuration lookups.
 *
 * It will prefer Laravel's config('ai-hub...') when available and gracefully
 * fall back to environment variables for non-Laravel contexts.
 */
final class ConfigResolver
{
    private string $root;

    public function __construct(string $root = 'ai-hub')
    {
        $this->root = $root;
    }

    /**
     * Get a configuration value.
     *
     * @param string $key Dot-delimited path relative to root, e.g. 'openai.api_key'
     * @param mixed $default Default if none found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Prefer Laravel's config() helper if present
        if (\function_exists('config')) {
            $val = \config($this->root . '.' . $key);
            if ($val !== null) {
                return $val;
            }
        }

        // Fallback: derive from environment variables using a simple convention
        // Convert e.g. 'drivers.openai.api_key' -> AI_OPENAI_API_KEY
        $envKey = $this->toEnvKey($key);

        $val = getenv($envKey);
        if ($val !== false && $val !== null) {
            return $val;
        }

        return $default;
    }

    /**
     * Get the default driver name.
     */
    public function defaultDriver(): string
    {
        $val = $this->get('default', 'openai');
        return is_string($val) && $val !== '' ? $val : 'openai';
    }

    /**
     * Get a driver config array merged with sensible defaults.
     */
    public function driverConfig(string $driver): array
    {
        $driver = strtolower($driver);

        if ($driver === 'openai') {
            return $this->resolveOpenAI();
        }

        if ($driver === 'anthropic') {
            return $this->resolveAnthropic();
        }

        // Unknown driver -> return empty config
        return [];
    }

    /**
     * Decode header config which may be JSON string or array.
     */
    public function decodeHeaders(mixed $headers): array
    {
        if (is_array($headers)) {
            return $headers;
        }
        if (is_string($headers) && $headers !== '') {
            $decoded = json_decode($headers, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    private function resolveOpenAI(): array
    {
        // This logic should mirror config/ai-hub/openai.php to support non-Laravel envs
        $apiKey = $this->get('openai.api_key', getenv('AI_OPENAI_API_KEY') ?: getenv('OPENAI_API_KEY'));
        $baseUrl = $this->get('openai.base_url', getenv('AI_OPENAI_BASE_URL') ?: getenv('OPENAI_BASE_URL'));
        $organization = $this->get('openai.organization', getenv('AI_OPENAI_ORG') ?: getenv('OPENAI_ORG'));
        $model = $this->get('openai.model') ?: getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';

        $headers = $this->decodeHeaders(
            $this->get('openai.headers', getenv('AI_OPENAI_HEADERS') ?: getenv('OPENAI_HEADERS'))
        );
        $timeout = (int) $this->get('openai.timeout', getenv('AI_OPENAI_TIMEOUT') ?: getenv('OPENAI_TIMEOUT') ?: 60);

        return [
            'api_key' => (string) $apiKey,
            'organization' => (string) $organization,
            'model' => (string) $model,
            'base_url' => rtrim((string) ($baseUrl ?: 'https://api.openai.com/v1'), '/'),
            'timeout' => $timeout,
            'headers' => $headers,
            'chat_path' => (string) $this->get('openai.chat_path', '/chat/completions'),
            'default_headers' => (array) $this->get('openai.default_headers', [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]),
        ];
    }

    private function resolveAnthropic(): array
    {
        // This logic should mirror config/ai-hub/anthropic.php to support non-Laravel envs
        $apiKey = $this->get('anthropic.api_key', getenv('AI_ANTHROPIC_API_KEY') ?: getenv('ANTHROPIC_API_KEY'));
        $baseUrl = $this->get('anthropic.base_url', getenv('AI_ANTHROPIC_BASE_URL') ?: getenv('ANTHROPIC_BASE_URL'));
        $model = $this->get('anthropic.model') ?: getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-sonnet';

        $headers = $this->decodeHeaders(
            $this->get('anthropic.headers', getenv('AI_ANTHROPIC_HEADERS') ?: getenv('ANTHROPIC_HEADERS'))
        );
        $timeout = (int) $this->get('anthropic.timeout', getenv('AI_ANTHROPIC_TIMEOUT') ?: getenv('ANTHROPIC_TIMEOUT') ?: 60);

        return [
            'api_key' => (string) $apiKey,
            'model' => (string) $model,
            'base_url' => rtrim((string) ($baseUrl ?: 'https://api.anthropic.com'), '/'),
            'timeout' => $timeout,
            'headers' => $headers,
            'messages_path' => (string) $this->get('anthropic.messages_path', '/v1/messages'),
            'default_headers' => (array) $this->get('anthropic.default_headers', [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]),
        ];
    }

    /**
     * Convert a config key path to a conventional env var.
     * openai.api_key -> AI_OPENAI_API_KEY
     */
    private function toEnvKey(string $key): string
    {
        $segments = explode('.', $key);
        // Map well-known prefixes
        // New structure: {driver}.foo -> AI_{DRIVER}_{FOO}
        if (count($segments) >= 2) {
            $driver = strtoupper((string) $segments[0]);
            $rest = array_slice($segments, 1);
            $restKey = strtoupper(implode('_', $rest));
            return 'AI_' . $driver . '_' . $restKey;
        }

        // Back-compat old structure: drivers.{driver}.foo -> AI_{DRIVER}_{FOO}
        if (count($segments) >= 3 && $segments[0] === 'drivers') {
            $driver = strtoupper((string) $segments[1]);
            $rest = array_slice($segments, 2);
            $restKey = strtoupper(implode('_', $rest));
            return 'AI_' . $driver . '_' . $restKey;
        }

        // Fallback: generic AI_HUB_ prefix
        return 'AI_HUB_' . strtoupper(str_replace('.', '_', $key));
    }
}
