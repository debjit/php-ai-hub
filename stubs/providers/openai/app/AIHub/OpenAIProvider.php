<?php

declare(strict_types=1);

namespace App\AIHub;

use App\AIHub\ProviderContract;
use App\AIHub\Support\ConfigResolver;

final class OpenAIProvider implements ProviderContract
{
    private ConfigResolver $config;

    public function __construct(?ConfigResolver $config = null)
    {
        $this->config = $config ?? new ConfigResolver('ai-hub');
    }

    /**
     * Return the effective provider name based on configured/default driver.
     * This allows reusing this class for openai-compatible providers like groq/openrouter.
     */
    public function name(): string
    {
        // Use configured default driver to decide the provider name dynamically
        return $this->config->defaultDriver();
    }

    /**
     * Return the default model for the current provider.
     */
    public function defaultModel(): string
    {
        $driver = $this->config->defaultDriver();
        $cfg = $this->config->driverConfig($driver);
        $model = (string) ($cfg['model'] ?? '');
        return $model !== '' ? $model : 'gpt-4o-mini';
    }
}
