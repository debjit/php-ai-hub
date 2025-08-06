# PHP AI Hub Flow

This document explains how the provider-agnostic flow works, how configuration is resolved, and how a request is executed through the shared components. It also covers how to add non-OpenAI-compatible providers.

## Overview

- Central config selects the active provider via `AI_HUB_DRIVER` (e.g., `openai`, `groq`, `openrouter`, or a custom one).
- OpenAI-compatible providers reuse the same `OpenAIProvider` class; only base URL, model, and API key differ.
- The shared `ConfigResolver` reads from Laravel `config()` when available, else from environment variables.
- The shared `HttpConnector` performs HTTP calls using the resolved configuration.
- The shared `AiChatService` constructs a standardized request and calls the provider’s endpoint.

## Files Involved

- `config/ai-hub/ai-hub.php`
  - Contains `"default"` driver name (falls back to `AI_HUB_DRIVER` or `AI_DRIVER` env).
- `config/ai-hub/providers.php`
  - Centralized configuration for OpenAI-compatible providers (openai/groq/openrouter) and any custom providers you add.
- `config/ai-hub/openai.php` (legacy)
  - Backwards-compatible settings for the `openai` driver.
- `src/Support/ConfigResolver.php`
  - Resolves config for the selected driver from `providers.php` (or `openai.php` for legacy).
- `src/OpenAIProvider.php`
  - Reusable provider class for OpenAI-compatible endpoints. Returns provider name and default model based on the active driver.
- `stubs/shared/app/AIHub/Connectors/HttpConnector.php`
  - Minimal HTTP client used by services to call the provider API.
- `stubs/shared/app/AIHub/Services/AiChatService.php`
  - Service orchestrating request building and calling `HttpConnector`.
- `src/ProviderContract.php`
  - Base interface that providers implement.

## Configuration Resolution Order

1) Determine current driver:
   - `ConfigResolver::defaultDriver()` reads `config('ai-hub.default')` or env `AI_HUB_DRIVER` (fallback `AI_DRIVER`), defaulting to `"openai"`.

2) Load driver configuration:
   - `ConfigResolver::driverConfig($driver)`:
     - First attempts to read `config("ai-hub.providers.{$driver}")` from `providers.php`.
     - If not found and `$driver === 'openai'`, falls back to `openai.php`.
     - In non-Laravel contexts, uses `AI_{DRIVER}_*` env variables.

3) Normalize configuration:
   - Ensures `base_url` is trimmed, `timeout` is int, `headers` JSON is decoded, and `chat_path`/`default_headers` have sensible defaults.

## OpenAI-Compatible Flow

This path applies to `openai`, `groq`, `openrouter` (and any other OpenAI-compatible providers).

1) You set `.env`:
   ```
   AI_HUB_DRIVER=openrouter
   AI_OPENROUTER_API_KEY=sk-or-v1-your-key
   AI_OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
   AI_OPENROUTER_MODEL=openrouter/horizon-beta
   # Optional for OpenRouter:
   AI_OPENROUTER_HEADERS={"HTTP-Referer":"https://your.app","X-Title":"Your App"}
   ```

2) `ConfigResolver`:
   - `defaultDriver(): openrouter`
   - `driverConfig('openrouter')` resolves to the `openrouter` block from `providers.php` (or env).

3) `OpenAIProvider`:
   - `name()` returns the active driver (e.g., `openrouter`).
   - `defaultModel()` fetches `model` from the resolved config.

4) `AiChatService` builds a chat request payload in OpenAI format:
   - Endpoint: `${base_url}${chat_path}` (e.g., `https://openrouter.ai/api/v1/chat/completions`)
   - Headers: `default_headers + headers` plus Authorization:
     - `Authorization: Bearer ${api_key}`
   - Body:
     ```
     {
       "model": "<resolved model>",
       "messages": [
         {"role":"system","content":"...optional..."},
         {"role":"user","content":"..."}
       ],
       "temperature": 0.7,
       "stream": false
     }
     ```

5) `HttpConnector` executes HTTP request and returns the response to `AiChatService`, which returns it to your calling code.

## Sequence Diagram (OpenAI-Compatible)

```
Caller
  |
  | 1. chat(messages) ---------------------------> AiChatService
  |                                              |
  |                    2. defaultDriver() -----> ConfigResolver
  |                                              |
  |                    3. driverConfig(driver) -> ConfigResolver (providers.php/env)
  |                                              |
  |                    4. defaultModel() ------> OpenAIProvider
  |                                              |
  | 5. Build payload/headers for OpenAI shape    |
  | 6. request(method,url,headers,body) -------> HttpConnector
  |                                              |
  | 7. perform HTTP call to provider API         |
  |                                              |
  | <----------------------- 8. response --------|
  |
  | <----------------------- 9. result ----------|
```

## Non-OpenAI-Compatible Providers

For providers that do not follow OpenAI’s API shape, you can add a custom provider class and wire it in `providers.php`.

1) Add provider to `config/ai-hub/providers.php`:
   ```
   'anthropic' => [
     'api_key'         => env('AI_ANTHROPIC_API_KEY'),
     'base_url'        => rtrim(env('AI_ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'), '/'),
     'model'           => env('AI_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20240620'),
     'timeout'         => (int) env('AI_ANTHROPIC_TIMEOUT', 60),
     'headers'         => env('AI_ANTHROPIC_HEADERS', ''), // e.g. {"anthropic-version":"2023-06-01"}
     'chat_path'       => env('AI_ANTHROPIC_CHAT_PATH', '/messages'),
     'default_headers' => [
       'Accept'       => 'application/json',
       'Content-Type' => 'application/json',
     ],
     'provider_class'  => \App\AIHub\AnthropicProvider::class,
   ],
   ```

2) Implement `\App\AIHub\AnthropicProvider`:
   - Implements `ProviderContract`.
   - Optionally expose helper methods used by your service layer, for example:
     - `endpoint(): string` → `"{$base_url}{$chat_path}"`
     - `headers(string $apiKey, array $defaultHeaders, array $extraHeaders): array`
     - `payload(array $messages, array $options): array` (convert your internal format to provider’s required payload)

3) Extend `AiChatService` or add a provider-aware adapter:
   - If the provider class exposes `payload()`/`headers()`/`endpoint()`, the service can branch based on the active provider’s `provider_class` to build requests appropriately.
   - For OpenAI-compatible, reuse the existing OpenAI-shaped payload builder.
   - For a non-compatible provider, call the provider’s custom methods.

4) Use via `.env`:
   ```
   AI_HUB_DRIVER=anthropic
   AI_ANTHROPIC_API_KEY=sk-your-key
   AI_ANTHROPIC_BASE_URL=https://api.anthropic.com/v1
   AI_ANTHROPIC_MODEL=claude-3-5-sonnet-20240620
   AI_ANTHROPIC_HEADERS={"anthropic-version":"2023-06-01"}
   ```

## How to Switch Providers

Set `AI_HUB_DRIVER` and the corresponding API key/model/base URL:

OpenAI:
```
AI_HUB_DRIVER=openai
AI_OPENAI_API_KEY=sk-your-openai-key
AI_OPENAI_BASE_URL=https://api.openai.com/v1
AI_OPENAI_MODEL=gpt-4o-mini
```

Groq:
```
AI_HUB_DRIVER=groq
AI_GROQ_API_KEY=gsk-your-groq-key
AI_GROQ_BASE_URL=https://api.groq.com/openai/v1
AI_GROQ_MODEL=llama3-8b-8192
```

OpenRouter:
```
AI_HUB_DRIVER=openrouter
AI_OPENROUTER_API_KEY=sk-or-v1-your-key
AI_OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
AI_OPENROUTER_MODEL=openrouter/horizon-beta
AI_OPENROUTER_HEADERS={"HTTP-Referer":"https://your.app","X-Title":"Your App"}
```

Custom (non-compatible) example – Anthropic:
```
AI_HUB_DRIVER=anthropic
AI_ANTHROPIC_API_KEY=sk-your-key
AI_ANTHROPIC_BASE_URL=https://api.anthropic.com/v1
AI_ANTHROPIC_MODEL=claude-3-5-sonnet-20240620
AI_ANTHROPIC_HEADERS={"anthropic-version":"2023-06-01"}
```

## Error Handling Notes

- If `providers.php` entry is missing for the active driver, `ConfigResolver` falls back to `openai.php` only when driver is `openai`.
- Ensure the API key environment variable for the active driver is set; otherwise, requests will fail with 401/403.
- IDE warnings about `env()` are benign outside Laravel; runtime resolution happens via Laravel config or environment variables.

## Extensibility Tips

- Keep `ProviderContract` minimal; expose additional provider-specific helper methods on custom providers as needed.
- If multiple non-compatible providers are added, consider introducing a formal `RequestAdapter` interface so `AiChatService` can call `adapter->endpoint()/headers()/payload()`.
- Maintain defaults in `providers.php`; let users override via environment variables without code changes.
