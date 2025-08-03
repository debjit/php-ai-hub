# PHP AI Hub (Laravel) — Portable, No-SDK

Portable AI Hub for Laravel. No vendor lock-in, no heavy SDK. This package exports files (stubs) into your Laravel app and lets you manage them via Composer commands.

## Key Changes

- Installed as a dev package and used as a Composer Plugin (no default Laravel service provider).
- Provides Composer-level commands:
  - `composer ai-hub:clean` — removes AI Hub files from your app.
  - `composer ai-hub:reset` — cleans and re-publishes the default stubs into your app.
- Ships with stubs for OpenAI (default) and Anthropic, and shared contracts/utilities.

## Requirements

- PHP 8.2+
- Laravel 10 or 11 (for the app consuming the stubs)
- Composer 2.2+ (Composer plugin API v2.2)
- Allow this plugin in Composer config

## Installation (DEV ONLY)

This package must be installed as a development dependency. It exports files into your app but is not required at runtime.

1) Allow this Composer plugin (one-time per project)

```
composer config allow-plugins.debjit/php-ai-hub true
```

2) Require the package as a dev dependency (DEV ONLY)

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

### Clean

Removes all exported AI Hub files from your app.

```
composer ai-hub:clean
```

This deletes:
- `config/ai-hub/`
- `app/AIHub/`

### Reset

Cleans and restores the default AI Hub files (stubs) into your app.

```
composer ai-hub:reset
```

What it does:
- Removes `config/ai-hub/` and `app/AIHub/`
- Copies defaults into your project from this package:
  - Shared app stubs: `stubs/shared/app` ➜ `app/`
  - Default provider (OpenAI) app stubs: `stubs/providers/openai/app` ➜ `app/`
  - Default provider configs: `stubs/providers/openai/config/ai-hub` ➜ `config/ai-hub/`

If you prefer Anthropic as default, you can manually copy from:
- `stubs/providers/anthropic/app/AIHub` ➜ `app/AIHub`
- `stubs/providers/anthropic/config/ai-hub` ➜ `config/ai-hub`

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

## Managing Providers

This package includes helper commands for managing providers (optional if you want to automate provider switching):

- `PhpAiHub\Console\AddProviderCommand`
- `PhpAiHub\Console\RemoveProviderCommand`

If you want to use these as Artisan commands, you can wire them in your app. By default, the Composer commands (`ai-hub:clean` / `ai-hub:reset`) are the primary interface.

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
  - Ensure Composer 2.2+ is installed.
- LSP/Intelephense errors in vendor context are harmless; commands execute via Composer.
- Windows paths: The commands compute project root via `getcwd()`; run from your project root.

## Contributing

PRs and issues welcome.

## License

MIT
