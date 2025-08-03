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
This shows end-to-end working with minimal setup. Swap to Anthropic later with `composer ai-hub:remove openai && composer ai-hub:add anthropic` and update env keys accordingly.

## Key Changes

- Installed as a dev package and used as a Composer Plugin (no default Laravel service provider).
- Provides Composer-level commands:
  - `composer ai-hub:clean` — removes AI Hub files from your app.
  - `composer ai-hub:reset` — cleans and re-publishes the default stubs into your app.
  - `composer ai-hub:add {provider} [--force|-f] [--tests|-t]` — add/scaffold a provider’s files into your app from stubs.
  - `composer ai-hub:remove {provider} [--force|-f]` — remove a provider’s scaffolded files from your app.
- Ships with stubs for OpenAI and Anthropic, plus shared contracts/utilities. There is no default provider; you must explicitly add one.

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

Anthropic:
```
# Install provider files into your app
composer ai-hub:add anthropic

# Set env variables (example)
# Add these to your .env
AI_HUB_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-your-anthropic-key
ANTHROPIC_API_BASE=https://api.anthropic.com
ANTHROPIC_API_MODEL=claude-3-5-sonnet-latest
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
- Does NOT install any provider by default. After reset, you must add a provider explicitly (see next section).

### Add a provider (scaffold into your app)

Installs provider-specific files from stubs into your app. Supports: `openai`, `anthropic`.

```
composer ai-hub:add openai
composer ai-hub:add anthropic
```

Options:
- `--force` / `-f`: overwrite existing files.
- `--tests` / `-t`: also copy tests if present under the provider stubs.

What is copied (if present for the provider):
- App code: `stubs/providers/{provider}/app/AIHub` ➜ `app/AIHub`
- Configs: `stubs/providers/{provider}/config/ai-hub` ➜ `config/ai-hub`
- Shared layer (only if missing or when forcing): `stubs/shared/app/AIHub` ➜ `app/AIHub`
- Optionally tests: `stubs/providers/{provider}/tests` ➜ `tests`

A lightweight registry file `ai-hub.json` in your project root tracks installed providers.

### Remove a provider

Removes provider-specific files previously scaffolded.

```
composer ai-hub:remove openai
composer ai-hub:remove anthropic
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
# or
composer ai-hub:add anthropic
```

## Quick Start Test (copy-paste)

After running `composer ai-hub:reset`, copy-paste these examples into your Laravel app to verify everything works. These use static code and the exported classes.

1) Example Artisan Command

Create file: `app/Console/Commands/AiHubExampleCommand.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AIHub\ChatClient;
use App\AIHub\Support\ConfigResolver;

class AiHubExampleCommand extends Command
{
    protected $signature = 'ai:example';
    protected $description = 'Test AI Hub ChatClient with a static prompt';

    public function handle(): int
    {
        $this->info('Running AI Hub example...');

        // Resolve provider from config/ai-hub/ai-hub.php
        $provider = ConfigResolver::resolveProvider();

        // Create ChatClient with the resolved provider
        $client = new ChatClient($provider);

        // Static prompt for testing
        $prompt = 'Say hello in one short sentence.';

        // Perform a static chat request (implementation depends on the stub you copied)
        $response = $client->chat($prompt);

        $this->line('Response:');
        $this->line((string) $response);

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
use App\AIHub\ChatClient;
use App\AIHub\Support\ConfigResolver;

Route::get('/ai-example', function () {
    // Resolve provider from config
    $provider = ConfigResolver::resolveProvider();

    // Create ChatClient with the resolved provider
    $client = new ChatClient($provider);

    // Static prompt for demo
    $prompt = 'Return a JSON object: {"hello":"world"}';

    // Perform a chat request
    $response = $client->chat($prompt);

    // Return plain text for quick verification
    return response((string) $response, 200, ['Content-Type' => 'text/plain']);
});
```

Visit: http://localhost:8000/ai-example (or your app URL)

Notes:
- The default config is under `config/ai-hub/`. Ensure appropriate environment variables (e.g., OPENAI_API_KEY) if your stub/provider requires them.
- The `App\AIHub\ChatClient` and `ConfigResolver` classes come from the exported stubs and may perform simple HTTP calls via `App\AIHub\Connectors\HttpConnector`.
- The example uses static strings to make copy-paste testing trivial.

## Available Providers

Out of the box providers (must be explicitly installed):
- openai
- anthropic

You can inspect the shipped stubs to see exactly what will be copied:

```
stubs/
  shared/
    app/AIHub/...
    config/ai-hub/...
  providers/
    openai/
      app/AIHub/...
      config/ai-hub/ai-hub.php
      config/ai-hub/openai.php
    anthropic/
      app/AIHub/AnthropicProvider.php
      config/ai-hub/anthropic.php
```

Provider management is handled via Composer commands listed above. Internally, the plugin registers command classes:
- Add: `PhpAiHub\Package\Console\AddProviderCommand` (composer name: `ai-hub:add`)
- Remove: `PhpAiHub\Package\Console\RemoveProviderCommand` (composer name: `ai-hub:remove`)
- Clean: `PhpAiHub\Composer\Commands\CleanComposerCommand` (composer name: `ai-hub:clean`)
- Reset: `PhpAiHub\Composer\Commands\ResetComposerCommand` (composer name: `ai-hub:reset`)

## Project Structure (exported into your app)

After `composer ai-hub:reset`, your Laravel app will have:

```
app/
  AIHub/
    ProviderContract.php
    Support/
      ConfigResolver.php
    Connectors/
      HttpConnector.php
    OpenAIProvider.php
    ChatClient.php
config/
  ai-hub/
    ai-hub.php
    openai.php
```

This code is your app code — edit freely.

## Rationale

- Keep your app’s AI integration portable and vendor-agnostic.
- No runtime dependency on SDKs — just simple HTTP connectors and contracts.
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
