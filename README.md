# PHP AI Hub (shadcn-style installer for Laravel)

Portable AI provider scaffolding for Laravel – no SDK lock-in, no vendor abstractions. Install once, then pull concrete provider code directly into your app like shadcn/ui.

## Features

- Composer plugin that adds:
  - `composer ai-hub:add <provider>` (install provider into your app)
  - `composer ai-hub:remove <provider>` (remove provider from your app)
- Copies real PHP source files into `app/AIHub` (not used from vendor)
- Copies provider configs into `config/ai-hub`
- Optional tests copying
- Tracks installed providers in `ai-hub.json`

Supported providers out of the box:
- `openai` (default)
- `anthropic`

More providers can be added by contributing stubs under `stubs/providers/<name>`.

---

## Requirements

- PHP 8.2+
- Laravel (tested with 10/11)
- Composer 2.x

---

## Installation

1) Require the package:

```
composer require debjit/php-ai-hub
```

2) Add the default provider (OpenAI):

```
composer ai-hub:add openai
```

This will copy:
- App classes into `app/AIHub/`
- Config files into `config/ai-hub/`
- Registry at project root: `ai-hub.json`

To add another provider later:

```
composer ai-hub:add anthropic
```

Options (add):
- `--force` Overwrite existing files
- `--tests` Copy tests if present in provider stubs

Examples (add):

```
composer ai-hub:add openai --force
composer ai-hub:add anthropic --tests
```

Remove a provider:

```
composer ai-hub:remove openai
composer ai-hub:remove anthropic -f
```

Notes on remove:
- Only provider-specific files/configs are removed (e.g., app/AIHub/* from that provider, config/ai-hub/{provider}.php).
- Shared config file config/ai-hub/ai-hub.php is not removed.
- After removal, empty directories under app/AIHub and config/ai-hub are cleaned up.
- The registry file ai-hub.json is updated to drop the provider entry.

---

## What gets installed

By default, for OpenAI:

App code (copied into your project)
- `app/AIHub/ProviderContract.php`
- `app/AIHub/Support/ConfigResolver.php`
- `app/AIHub/OpenAIProvider.php`
- `app/AIHub/ChatClient.php`
- `app/AIHub/Connectors/HttpConnector.php`

Config
- `config/ai-hub/ai-hub.php` (base)
- `config/ai-hub/openai.php` (OpenAI-specific)

Anthropic adds:
- `app/AIHub/AnthropicProvider.php`
- `config/ai-hub/anthropic.php`

Note: files are copied to your app. You fully own and modify them.

---

## Configuration

The base config lives in:
- `config/ai-hub/ai-hub.php`

Default driver:
```
'default' => env('AI_DEFAULT', 'openai')
```

OpenAI config (`config/ai-hub/openai.php`):
- `AI_OPENAI_BASE_URL` (default: `https://api.openai.com/v1`)
- `AI_OPENAI_API_KEY`
- `AI_OPENAI_HEADERS` (JSON or associative array)
- `AI_OPENAI_CHAT_PATH` (default: `/chat/completions`)
- `AI_OPENAI_TIMEOUT` (default: `60`)
- `AI_OPENAI_DEFAULT_MODEL` (default: `gpt-4o-mini`)

Anthropic config (`config/ai-hub/anthropic.php`):
- `AI_ANTHROPIC_BASE_URL` (default: `https://api.anthropic.com`)
- `AI_ANTHROPIC_API_KEY`
- `AI_ANTHROPIC_HEADERS` (JSON or associative array)
- `AI_ANTHROPIC_MESSAGES_PATH` (default: `/v1/messages`)
- `AI_ANTHROPIC_TIMEOUT` (default: `60`)
- `AI_ANTHROPIC_DEFAULT_MODEL` (default: `claude-3-haiku`)

---

## Basic usage example

Resolve config and call OpenAI chat:

```php
use App\AIHub\Support\ConfigResolver;
use App\AIHub\Connectors\HttpConnector;
use App\AIHub\ChatClient;

$config = new ConfigResolver('ai-hub');
$http = new HttpConnector($config);
$chat = new ChatClient($http);

$response = $chat->chat([
    ['role' => 'user', 'content' => 'Hello AI!'],
], [
    // optional overrides:
    // 'model' => 'gpt-4o-mini',
    // 'base_url' => 'https://api.openai.com/v1',
    // 'api_key' => 'sk-...',
    // 'headers' => ['X-Org' => 'acme'],
]);

if ($response['error'] !== null) {
    logger()->error('AI Error', $response);
} else {
    // $response contains:
    // ['status' => int, 'headers' => array, 'body' => array|null, 'raw' => string, 'error' => null]
    dd($response);
}
```

Switch default provider to Anthropic in `config/ai-hub/ai-hub.php`:

```php
'default' => 'anthropic',
```

Then consume its config via `ConfigResolver('ai-hub')` and build your client accordingly.

---

## Installed providers registry

The command writes to `ai-hub.json` at the project root:

```json
{
  "providers": {
    "openai": { "installed_at": "2025-08-02T12:34:56+00:00" },
    "anthropic": { "installed_at": "2025-08-02T12:35:10+00:00" }
  }
}
```

You can safely delete entries if you manually remove copied files.

---

## Updating scaffolded code

Because code is copied into your app, future updates will not overwrite local changes unless you pass `--force`.

- To refresh provider code from the latest stubs:
  ```
  composer update debjit/php-ai-hub
  composer ai-hub:add openai --force
  ```

---

## Adding tests

If a provider includes tests under its stubs, you can copy them with:

```
composer ai-hub:add openai --tests
```

They will be placed under your project's `tests/` directory, following the stub structure.

---

## Notes

- IDE warnings about `env()` / `config()` undefined in the stubs while in the package are expected. Once copied into a Laravel app, these helpers exist.
- The plugin does not auto-run any post-install script by default to avoid side effects. If you want OpenAI auto-installed on `composer require`, we can add a hook to run `ai-hub:add openai` automatically.

---

## Roadmap

- More providers (Google, Azure OpenAI, Mistral, etc.)
- Optional E2E stub tests and example controllers
- Remove/provider command (uninstall) ✔️
- Update/merge strategy for local modifications

---

## License

MIT
