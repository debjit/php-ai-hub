# PHP AI Hub (Laravel) — Portable, No-SDK

Portable AI Hub for Laravel. No vendor lock-in, no heavy SDK. This package exports files (stubs) into your Laravel app and lets you manage them via Composer commands.

## Quick Start (OpenAI in 3 steps)

1) Install as a dev dependency and allow the Composer plugin
```
composer config allow-plugins.debjit/php-ai-hub true
composer require --dev debjit/php-ai-hub
```

2) Scaffold OpenAI provider files into your app
```
composer ai-hub:add openai
```

3) Set env and run the example
Add to your .env:
```
AI_HUB_PROVIDER=openai
OPENAI_API_KEY=sk-your-openai-key
OPENAI_API_BASE=https://api.openai.com
OPENAI_API_MODEL=gpt-4o-mini
```

Now run either:
```
# Artisan example (after registering the example command as shown below)
php artisan ai:example

# Or hit the demo route (after adding the route shown below)
GET /ai-example
```
This shows end-to-end working with minimal setup.

## Key Changes

- Installed as a dev package and used as a Composer Plugin (no default Laravel service provider).
- Provides Composer-level commands:
  - `composer ai-hub:clean` — removes AI Hub files from your app.
  - `composer ai-hub:reset` — cleans and re-publishes the default stubs into your app.
  - `composer ai-hub:add {provider} [--force|-f] [--tests|-t]` — add/scaffold a provider’s files into your app from stubs.
  - `composer ai-hub:remove {provider} [--force|-f]` — remove a provider’s scaffolded files from your app.
- Ships with provider-agnostic shared stubs plus provider-specific stubs (e.g., OpenAI). Shared stubs are installed first; provider stubs layer on top. There is no default provider; you must explicitly add one.

## Requirements

- PHP 8.2+
- Laravel 10+ (for the app consuming the stubs)
- Composer 2.x (Composer plugin API ^2.0, runtime API ^2.2)
- Allow this plugin in Composer config

## Installation (DEV ONLY)

This package must be installed as a development dependency. It exports files into your app but is not required at runtime.

1) Allow this Composer plugin (one-time per project)

```
composer config allow-plugins.debjit/php-ai-hub true
```

2) Require the package as a dev dependency (DEV ONLY)

Use --dev so it is only installed for development:
```
composer require --dev debjit/php-ai-hub
```

If you need to target the development branch explicitly (e.g., before a tagged release):
```
composer require --dev debjit/php-ai-hub:dev-main
```

3) Do NOT install in production

- Ensure you do NOT add this package to non-dev dependencies.
- Your production deploy should not require dev dependencies (e.g., use --no-dev).
- The exported files under app/ and config/ remain in your repository and are all your app needs at runtime.

Notes:
- Package type is `composer-plugin` and will auto-register its plugin (in dev).
- The package does not provide runtime services; it exports files only.

## Usage

Run Composer commands from your Laravel project root:

### 1) Add a provider first (required)

There is no default provider. Install one explicitly so you can copy-paste and run immediately.

OpenAI (recommended to start):
```
# Install provider files into your app
composer ai-hub:add openai

# Set env variables (example)
# Add these to your .env
AI_HUB_PROVIDER=openai
OPENAI_API_KEY=sk-your-openai-key
OPENAI_API_BASE=https://api.openai.com
OPENAI_API_MODEL=gpt-4o-mini
```

After adding a provider and env vars, you can use the quick-start examples below.

### 2) Clean

### Clean

Removes all exported AI Hub files from your app.

```
composer ai-hub:clean
```

This deletes:
- `config/ai-hub/`
- `app/AIHub/`

### Reset

Cleans your app’s AI Hub directories and restores only the shared scaffolding (no provider).

```
composer ai-hub:reset
```

What it does:
- Removes `config/ai-hub/` and `app/AIHub/`
- Copies shared scaffolding into your project from this package:
  - Shared app stubs: `stubs/shared/app` ➜ `app/`
  - Shared config stubs: `stubs/shared/config/ai-hub` ➜ `config/ai-hub`
- Does NOT install any provider by default. After reset, you must add a provider explicitly (see next section).

### Add a provider (scaffold into your app)

Installs provider-specific files from stubs into your app. Supports: `openai`.

```
composer ai-hub:add openai
```

Options:
- `--force` / `-f`: overwrite existing files.
- `--tests` / `-t`: also copy tests if present under the provider stubs.

What is copied (in order):
1) Shared layer (always first):
   - `stubs/shared/app/AIHub` ➜ `app/AIHub`
   - `stubs/shared/config/ai-hub` ➜ `config/ai-hub`
2) Provider-specific layer (overrides if present):
   - `stubs/providers/{provider}/app/AIHub` ➜ `app/AIHub`
   - `stubs/providers/{provider}/config/ai-hub` ➜ `config/ai-hub`
3) Optionally tests:
   - `stubs/providers/{provider}/tests` ➜ `tests`

A lightweight registry file `ai-hub.json` in your project root tracks installed providers.

### Remove a provider

Removes provider-specific files previously scaffolded.

```
composer ai-hub:remove openai
```

Options:
- `--force` / `-f`: skip the confirmation prompt.

What is removed:
- Provider config: `config/ai-hub/{provider}.php`
- Provider app files mirrored from this package’s stubs for that provider (detected dynamically)
- Then any now-empty directories under `app/AIHub` and `config/ai-hub` are cleaned up

Shared config `config/ai-hub/ai-hub.php` is NOT removed.

After a reset, pick and add your provider explicitly, e.g.:
```
composer ai-hub:add openai
```

## Quick Start Test (copy-paste)

After running `composer ai-hub:reset` (shared layer only) and then `composer ai-hub:add openai` (provider layer), copy-paste these examples into your Laravel app to verify everything works. These use the exported shared HttpConnector and AiChatService.

1) Example Artisan Command

Create file: `app/Console/Commands/AiHubExampleCommand.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AIHub\Services\AiChatService;
use App\AIHub\Connectors\HttpConnector;

class AiHubExampleCommand extends Command
{
    protected $signature = 'ai:example';
    protected $description = 'Test AI Hub AiChatService with a static prompt';

    public function handle(): int
    {
        $this->info('Running AI Hub example...');

        // Uses shared HttpConnector and resolves config from .env or config/ai-hub
        $http = new HttpConnector();
        $chat = new AiChatService($http);

        // Static prompt for testing
        $messages = [['role' => 'user', 'content' => 'Say hello in one short sentence.']];

        // Perform a chat request via shared AiChatService
        $response = $chat->chat($messages);

        $this->line('Response:');
        $this->line((string) ($response['body']['choices'][0]['message']['content'] ?? $response['raw'] ?? ''));

        $this->info('Done.');
        return self::SUCCESS;
    }
}
```

Register it in `app/Console/Kernel.php`:

```php
protected $commands = [
    \App\Console\Commands\AiHubExampleCommand::class,
];
```

Run:

```
php artisan ai:example
```

2) Example Web Route

Add to `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\AIHub\Services\AiChatService;
use App\AIHub\Connectors\HttpConnector;

Route::get('/ai-example', function () {
    // Uses shared HttpConnector and AiChatService
    $http = new HttpConnector();
    $chat = new AiChatService($http);

    // Static prompt for demo
    $messages = [['role' => 'user', 'content' => 'Return a JSON object: {"hello":"world"}']];

    // Perform a chat request
    $response = $chat->chat($messages);

    // Return plain text for quick verification
    return response((string) ($response['body']['choices'][0]['message']['content'] ?? $response['raw'] ?? ''), 200, ['Content-Type' => 'text/plain']);
});
```

Visit: http://localhost:8000/ai-example (or your app URL)

Notes:
- The default config is under `config/ai-hub/`. Ensure appropriate environment variables (e.g., OPENAI_API_KEY, OPENAI_API_BASE, OPENAI_API_MODEL).
- The `App\AIHub\Connectors\HttpConnector` and `App\AIHub\Services\AiChatService` are shared stubs and will be present after `reset` and `add`.
- The example uses the shared AiChatService to demonstrate provider-agnostic usage; provider-specific settings are read from config/env.

## Available Providers

Out of the box providers (must be explicitly installed):
- openai

You can inspect the shipped stubs to see exactly what will be copied:

```
stubs/
  shared/
    app/AIHub/
      ProviderContract.php
      Support/ConfigResolver.php
      Connectors/HttpConnector.php
      Services/AiChatService.php
    config/ai-hub/
      ai-hub.php
  providers/
    openai/
      app/AIHub/
        OpenAIProvider.php
      config/ai-hub/
        openai.php
```

Provider management is handled via Composer commands listed above. Internally, the plugin registers command classes:
- Add: `PhpAiHub\Package\Console\AddProviderCommand` (composer name: `ai-hub:add`)
- Remove: `PhpAiHub\Package\Console\RemoveProviderCommand` (composer name: `ai-hub:remove`)
- Clean: `PhpAiHub\Composer\Commands\CleanComposerCommand` (composer name: `ai-hub:clean`)
- Reset: `PhpAiHub\Composer\Commands\ResetComposerCommand` (composer name: `ai-hub:reset`)

## Project Structure (exported into your app)

After `composer ai-hub:reset`, your Laravel app will have the shared layer only:

```
app/
  AIHub/
    ProviderContract.php
    Support/
      ConfigResolver.php
    Connectors/
      HttpConnector.php
    Services/
      AiChatService.php
config/
  ai-hub/
    ai-hub.php
```

After `composer ai-hub:add openai`, provider-specific files are layered on top:

```
app/
  AIHub/
    ProviderContract.php
    Support/
      ConfigResolver.php
    Connectors/
      HttpConnector.php
    Services/
      AiChatService.php
    OpenAIProvider.php
config/
  ai-hub/
    ai-hub.php
    openai.php
```

This code is your app code — edit freely.

## Rationale

- Keep your app’s AI integration portable and vendor-agnostic.
- No runtime dependency on SDKs — just simple HTTP connectors and contracts.
- Shared stubs centralize common code so providers stay lean and do not overwrite each other.
- Manage the files via Composer for consistent install/reset across environments.

## Troubleshooting

- Composer plugin not running:
  - Ensure you have: `composer config allow-plugins.debjit/php-ai-hub true`
- Ensure Composer 2.x is installed (plugin API ^2.0, runtime API ^2.2).
- LSP/Intelephense errors in vendor context are harmless; commands execute via Composer.
- Windows paths: The commands compute project root via `getcwd()`; run from your project root.

## Contributing

PRs and issues welcome.

## License

MIT
