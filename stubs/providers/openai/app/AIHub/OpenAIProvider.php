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
    public function name(): string
    {
        return 'openai';
    }

    public function defaultModel(): string
    {
        return 'gpt-4o-mini';
    }
}
