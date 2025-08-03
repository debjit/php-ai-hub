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

## Installation (as a dev package)

1) Allow this Composer plugin (one-time per project)

```
composer config allow-plugins.debjit/php-ai-hub true
```

2) Require the package as a dev dependency

```
composer require --dev debjit/php-ai-hub:dev-main
```

Notes:
- This package type is `composer-plugin` and will auto-register its plugin.
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
