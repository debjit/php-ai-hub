<?php

declare(strict_types=1);

namespace App\AIHub;

use App\AIHub\ProviderContract;
use App\AIHub\Support\ConfigResolver;

final class AnthropicProvider implements ProviderContract
{
    private ConfigResolver $config;

    public function __construct(?ConfigResolver $config = null)
    {
        $this->config = $config ?? new ConfigResolver('ai-hub');
    }
    public function name(): string
    {
        return 'anthropic';
    }

    public function defaultModel(): string
    {
        return 'claude-3-haiku';
    }
}
