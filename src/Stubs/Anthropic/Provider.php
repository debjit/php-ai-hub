<?php

declare(strict_types=1);

namespace App\Services\AI\Providers\Anthropic;

use App\Services\AI\Providers\ProviderContract;

use App\Services\AI\Support\ConfigResolver;

final class Provider implements ProviderContract
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
