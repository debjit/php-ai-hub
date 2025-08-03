<?php

declare(strict_types=1);

namespace App\AIHub\Connectors;

use App\AIHub\Support\ConfigResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

final class HttpConnector
{
    private ConfigResolver $config;

    public function __construct(?ConfigResolver $config = null)
    {
        $this->config = $config ?? new ConfigResolver('ai-hub');
    }

    /**
     * Resolve configuration from environment variables first, then from a config array if provided.
     * This design allows drop-in replacement of any OpenAI-compatible base URL with headers.
     */
    public function config(array $override = []): array
    {
        // Resolve from ai-hub config with graceful env fallback
        $cfg = $this->config->driverConfig('openai');

        // Apply overrides last
        if (!empty($override)) {
            // headers can be array or JSON string; normalize before merging
            if (array_key_exists('headers', $override)) {
                $override['headers'] = $this->config->decodeHeaders($override['headers']);
            }

            $cfg = array_replace($cfg, array_filter($override, static function ($v) {
                return $v !== null;
            }));
        }

        return $cfg;
    }

    /**
     * Build the final headers array, including Authorization if api_key is provided,
     * and a merge of default + custom headers.
     */
    public function buildHeaders(array $cfg): array
    {
        $headers = array_merge($cfg['default_headers'] ?? [], $cfg['headers'] ?? []);
        if (!empty($cfg['api_key'])) {
            // Allow custom Authorization to override if set in headers
            $headers['Authorization'] = $headers['Authorization'] ?? ('Bearer ' . $cfg['api_key']);
        }
        return $headers;
    }

    /**
     * Execute a JSON POST request using Laravel's HTTP client.
     * Returns an array with keys: status, headers, body (decoded array|null), raw (string).
     */
    public function postJson(string $url, array $headers, array $payload, int $timeout = 60): array
    {
        /** @var Response $resp */
        $resp = Http::withHeaders($headers)
            ->timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        $status = $resp->status();
        $raw = $resp->body();

        // Laravel may decode JSON with ->json(); ensure it's an array
        $decoded = null;
        try {
            $data = $resp->json();
            if (is_array($data)) {
                $decoded = $data;
            }
        } catch (\Throwable $e) {
            // keep $decoded as null if body isn't valid JSON
        }

        // Response headers come as array<string, array<string>>
        $headersOut = [];
        foreach ($resp->headers() as $key => $values) {
            if (is_array($values)) {
                // Join multi-value headers with comma as is common for HTTP
                $headersOut[$key] = implode(', ', $values);
            } else {
                $headersOut[$key] = (string) $values;
            }
        }

        return [
            'status'  => $status,
            'headers' => $headersOut,
            'body'    => $decoded,
            'raw'     => $raw,
        ];
    }
}
