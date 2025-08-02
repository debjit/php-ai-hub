# PHP AI Hub (Laravel) — Use AI providers without their SDKs

A lightweight Laravel package that provides a unified way to call AI providers (like OpenAI and Anthropic) using simple HTTP connectors and provider stubs — no vendor SDKs required.

This package focuses on:
- Simple configuration per provider
- Minimal surface area to call common AI endpoints (e.g., chat/completions)
- Extensibility to add new providers without pulling their full SDKs

## Requirements

- PHP 8.2+
- Laravel 10 or 11
- Composer

## Installation

```bash
composer require debjit/php-ai-hub
```

Laravel package discovery should auto-register the service provider.

## Publish Configuration

```bash
php artisan vendor:publish --provider="Debjit\PhpAiHub\Installer" --tag=ai-hub-config
```

This will publish:
- `config/ai-hub.php` — main hub config
- `config/ai-hub/openai.php` — OpenAI-specific config
- `config/ai-hub/anthropic.php` — Anthropic-specific config

If these files already exist, review and merge as needed.

## Environment Variables

Set the required keys for the providers you intend to use.

OpenAI:
```
OPENAI_API_KEY=sk-...
OPENAI_BASE_URL=https://api.openai.com
OPENAI_ORG_ID=org_...(optional)
OPENAI_PROJECT_ID=proj_...(optional)
```

Anthropic:
```
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_BASE_URL=https://api.anthropic.com
ANTHROPIC_VERSION=2023-06-01
```

You can override the base URLs to route through proxies/gateways if needed.

## Configuration Overview

Main hub configuration: `config/ai-hub.php`
- Controls default provider and maps provider identifiers to resolver classes and stub implementations.

Example (high-level):
```php
return [
    'default' => env('AI_HUB_DEFAULT_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'config' => base_path('config/ai-hub/openai.php'),
            'provider' => \Debjit\PhpAiHub\Stubs\OpenAI\Provider::class,
        ],
        'anthropic' => [
            'config' => base_path('config/ai-hub/anthropic.php'),
            'provider' => \Debjit\PhpAiHub\Stubs\Anthropic\Provider::class,
        ],
    ],
];
```

Per-provider configuration:
- `config/ai-hub/openai.php` contains base URL, auth header strategy, default model, timeouts, etc.
- `config/ai-hub/anthropic.php` contains base URL, version header, default model, timeouts, etc.

How config is resolved:
- `Debjit\PhpAiHub\Support\ConfigResolver` reads the main hub config, then loads the targeted provider config file.
- Environment variables are read via Laravel `env()` in those configs.
- Missing or invalid configurations will throw descriptive exceptions early.

## Core Concepts

- Registry: `Debjit\PhpAiHub\Registry`
  - Entry point to get a provider instance by key (e.g., "openai", "anthropic") or the default.
- Provider Stub: `Debjit\PhpAiHub\Stubs\{Provider}\Provider`
  - Facade-like API that exposes high-level operations (e.g., chat).
- Client/Connector: `Debjit\PhpAiHub\Stubs\{Provider}\Connectors\HttpConnector`
  - Responsible for HTTP requests to the provider with proper headers, endpoints, and payload shape.

You don’t install SDKs for each provider: we model the minimal HTTP required.

## Quick Start

Resolve the default provider (configured via `AI_HUB_DEFAULT_PROVIDER` or `config/ai-hub.php`):

```php
use Debjit\PhpAiHub\Registry;

$provider = app(Registry::class)->provider(); // default from config
```

Or explicitly:

```php
$openai = app(Registry::class)->provider('openai');
$anthropic = app(Registry::class)->provider('anthropic');
```

### Example: OpenAI Chat Completion

```php
use Debjit\PhpAiHub\Registry;

$openai = app(Registry::class)->provider('openai');

$response = $openai->chat()->create([
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Write a haiku about Laravel.'],
    ],
    'temperature' => 0.7,
]);

$content = $response['choices'][0]['message']['content'] ?? null;
```

Notes:
- The stub normalizes the request to OpenAI’s `/v1/chat/completions`.
- Auth header `Authorization: Bearer {OPENAI_API_KEY}` is added by the connector.

### Example: Anthropic Messages

```php
use Debjit\PhpAiHub\Registry;

$anthropic = app(Registry::class)->provider('anthropic');

$response = $anthropic->chat()->create([
    'model' => 'claude-3-5-sonnet-20240620',
    'messages' => [
        ['role' => 'user', 'content' => 'Summarize Laravel service providers.'],
    ],
    'max_tokens' => 512,
]);

$content = $response['content'][0]['text'] ?? null;
```

Notes:
- The stub targets Anthropic’s `/v1/messages` with headers:
  - `x-api-key: {ANTHROPIC_API_KEY}`
  - `anthropic-version: {ANTHROPIC_VERSION}`

### Streaming (if supported)

If a provider supports streaming, the stub may expose a streaming method:

```php
$stream = $openai->chat()->stream([
    'model' => 'gpt-4o-mini',
    'messages' => [/*...*/],
]);

foreach ($stream as $event) {
    // handle tokens/chunks
}
```

Refer to the specific provider stub for supported features.

## Service Container Usage

You can type-hint the registry wherever needed:

```php
use Debjit\PhpAiHub\Registry;

class MyController
{
    public function __construct(private Registry $registry) {}

    public function __invoke()
    {
        $ai = $this->registry->provider(); // default
        // ...
    }
}
```

## Adding a New Provider

1) Create a provider config: `config/ai-hub/{provider}.php`
   - Base URL, auth headers, defaults, timeouts/retries.

2) Implement a connector:
   - Example: `src/Stubs/FooAI/Connectors/HttpConnector.php`
   - Use Laravel HTTP client, apply headers and base URL from config.
   - Provide methods like `chatCompletions(array $payload): array`.

3) Implement a provider stub:
   - Example: `src/Stubs/FooAI/Provider.php`
   - Expose simplified methods, like `chat()->create($payload)` that internally calls the connector.
   - Keep the public API consistent with existing providers when possible.

4) Register in main config:
   - In `config/ai-hub.php` add the mapping:
     ```php
     'providers' => [
         // ...
         'fooai' => [
             'config' => base_path('config/ai-hub/fooai.php'),
             'provider' => \Debjit\PhpAiHub\Stubs\FooAI\Provider::class,
         ],
     ],
     ```

5) Use it:
   ```php
   $foo = app(\Debjit\PhpAiHub\Registry::class)->provider('fooai');
   $foo->chat()->create([...]);
   ```

Guidelines:
- Do not use the provider’s official SDK. Prefer HTTP with minimal dependencies.
- Normalize payloads where possible to keep your app code portable between providers.

## Error Handling & Troubleshooting

- Missing API key or base URL
  - Ensure `.env` variables are set and the provider config reads them via `env()`.

- Invalid provider key
  - Confirm the provider key exists in `config/ai-hub.php` under `providers`.

- HTTP errors (4xx/5xx)
  - The connector will return the response or throw exceptions based on your implementation. Check your timeouts and retry logic in the provider config.

- Config resolution issues
  - `Debjit\PhpAiHub\Support\ConfigResolver` loads `config/ai-hub.php`, validates provider entry, and then loads the provider’s own config. If paths or classes are wrong, you’ll get descriptive exceptions.

## Testing

- Mock the connector classes and assert payload shapes.
- For integration tests, set fake API keys and use Laravel’s HTTP fake to simulate provider responses.

## Security

- Never log raw API keys or entire request/response bodies containing sensitive data.
- Prefer setting keys in `.env` and referencing via config files.

## Versioning

Follow semantic versioning (SemVer). Breaking changes will bump the major version.

## License

MIT License. See LICENSE file.

## Credits

Crafted for teams that prefer HTTP-first integrations with AI providers in Laravel without heavyweight SDKs.
