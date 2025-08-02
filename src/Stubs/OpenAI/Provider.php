<?php

declare(strict_types=1);

namespace App\Services\AI\Providers\OpenAI;

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
        return 'openai';
    }

    public function defaultModel(): string
    {
        return 'gpt-4o-mini';
    }
}
