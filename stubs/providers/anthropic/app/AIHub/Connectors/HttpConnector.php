<?php

declare(strict_types=1);

namespace App\AIHub\Connectors;

use App\AIHub\Support\ConfigResolver;

final class HttpConnector
{
    private ConfigResolver $config;

    public function __construct(?ConfigResolver $config = null)
    {
        $this->config = $config ?? new ConfigResolver('ai-hub');
    }
    /**
     * Resolve configuration from environment variables first, then from a config array if provided.
     * This design allows drop-in replacement of any Anthropic-compatible base URL with headers.
     */
    public function config(array $override = []): array
    {
        // Resolve from ai-hub config with graceful env fallback
        $cfg = $this->config->driverConfig('anthropic');

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
     * Build the final headers array, including x-api-key if api_key is provided,
     * and a merge of default + custom headers.
     */
    public function buildHeaders(array $cfg): array
    {
        $headers = array_merge($cfg['default_headers'] ?? [], $cfg['headers'] ?? []);
        if (!empty($cfg['api_key'])) {
            // Allow custom x-api-key to override if set in headers
            $headers['x-api-key'] = $headers['x-api-key'] ?? $cfg['api_key'];
        }
        return $headers;
    }

    /**
     * Execute a JSON POST request using curl if available; fallback to file_get_contents.
     * Returns an array with keys: status, headers, body (decoded array|null), raw (string).
     */
    public function postJson(string $url, array $headers, array $payload, int $timeout = 60): array
    {
        $json = json_encode($payload);

        // Prefer cURL if available
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $h = [];
            foreach ($headers as $k => $v) {
                $h[] = $k . ': ' . $v;
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => $h,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_HEADER         => true, // capture headers + body
            ]);

            $response = curl_exec($ch);
            $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                return [
                    'status' => 0,
                    'headers' => [],
                    'body' => null,
                    'raw' => '',
                    'error' => $err ?: 'Unknown cURL error',
                ];
            }

            $rawHeaders = substr($response, 0, $headerSize);
            $rawBody    = substr($response, $headerSize);
            $decoded    = json_decode($rawBody, true);

            return [
                'status'  => $status,
                'headers' => $this->parseHeaders($rawHeaders),
                'body'    => is_array($decoded) ? $decoded : null,
                'raw'     => $rawBody,
            ];
        }

        // Fallback: streams
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => $this->formatHeaders($headers),
                'content' => $json,
                'timeout' => $timeout,
                'ignore_errors' => true, // capture non-2xx body
            ],
        ]);
        $raw = file_get_contents($url, false, $context);
        $status = $this->extractStatusCode($http_response_header ?? []);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return [
            'status'  => $status,
            'headers' => $this->normalizeResponseHeaders($http_response_header ?? []),
            'body'    => is_array($decoded) ? $decoded : null,
            'raw'     => $raw ?: '',
        ];
    }

    /**
     * Helper to format headers array into "Key: Value" lines for streams.
     */
    private function formatHeaders(array $headers): string
    {
        $lines = [];
        foreach ($headers as $k => $v) {
            $lines[] = $k . ': ' . $v;
        }
        return implode("\r\n", $lines);
    }

    private function extractStatusCode(array $headers): int
    {
        foreach ($headers as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                return (int) $m[1];
            }
        }
        return 0;
    }

    private function normalizeResponseHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $h) {
            $parts = explode(':', $h, 2);
            if (count($parts) === 2) {
                $out[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $out;
    }

    private function parseHeaders(string $raw): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $out[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $out;
    }
}
