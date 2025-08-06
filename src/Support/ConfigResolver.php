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
            $config = 'config';
            $val = $config($this->root . '.' . $key);
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

        // First try centralized providers.php if present
        $fromConfig = $this->resolveFromProvidersConfig($driver);
        if (!empty($fromConfig)) {
            return $fromConfig;
        }

        // Back-compat: explicit resolver for built-in openai.php
        if ($driver === 'openai') {
            return $this->resolveOpenAI();
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
        // Mirror config/ai-hub/openai.php; avoid duplicating getenv fallbacks here.
        return [
            'api_key' => (string) $this->get('openai.api_key'),
            'organization' => (string) $this->get('openai.organization'),
            'model' => (string) $this->get('openai.model'),
            'base_url' => rtrim((string) ($this->get('openai.base_url') ?: 'https://api.openai.com/v1'), '/'),
            'timeout' => (int) ($this->get('openai.timeout') ?: 60),
            'headers' => $this->decodeHeaders($this->get('openai.headers', '')),
            'chat_path' => (string) ($this->get('openai.chat_path') ?: '/chat/completions'),
            'default_headers' => (array) $this->get('openai.default_headers', [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]),
        ];
    }

    /**
     * Resolve provider config from centralized config/ai-hub/providers.php if available.
     * Falls back to environment using conventional AI_{PROVIDER}_* keys when config() is not available.
     */
    private function resolveFromProvidersConfig(string $driver): array
    {
        $driver = strtolower($driver);

        // If Laravel config() exists, try config('ai-hub.providers.{driver}')
        if (\function_exists('config')) {
            $config = 'config';
            $arr = $config($this->root . '.providers.' . $driver);
            if (is_array($arr) && !empty($arr)) {
                // normalize headers possibly being JSON string
                if (isset($arr['headers'])) {
                    $arr['headers'] = $this->decodeHeaders($arr['headers']);
                }
                // enforce required defaults
                $arr['base_url'] = rtrim((string) ($arr['base_url'] ?? ''), '/');
                $arr['timeout'] = (int) ($arr['timeout'] ?? 60);
                $arr['chat_path'] = (string) ($arr['chat_path'] ?? '/chat/completions');
                $arr['default_headers'] = (array) ($arr['default_headers'] ?? [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);
                return $arr;
            }
        }

        // Non-Laravel context: build from env using AI_{DRIVER}_* variables
        // Keys we support in providers.php
        $keys = [
            'api_key', 'organization', 'model', 'base_url', 'timeout', 'headers', 'chat_path', 'default_headers',
        ];

        $resolved = [];
        foreach ($keys as $k) {
            // Prefer explicit env AI_{DRIVER}_{KEY}
            $envKey = 'AI_' . strtoupper($driver) . '_' . strtoupper(str_replace('-', '_', $k));
            $val = getenv($envKey);

            if ($val !== false && $val !== null) {
                $resolved[$k] = $val;
            }
        }

        if (!empty($resolved)) {
            // Type/format normalization
            $resolved['base_url'] = rtrim((string) ($resolved['base_url'] ?? ''), '/');
            $resolved['timeout'] = (int) ($resolved['timeout'] ?? 60);
            $resolved['headers'] = $this->decodeHeaders($resolved['headers'] ?? '');
            $resolved['chat_path'] = (string) ($resolved['chat_path'] ?? '/chat/completions');
            $resolved['default_headers'] = is_array($resolved['default_headers'] ?? null)
                ? $resolved['default_headers']
                : [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ];
            return $resolved;
        }

        return [];
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
