<?php

declare(strict_types=1);

namespace App\Services\AI\Providers;

interface ProviderContract
{
    public function name(): string;

    public function defaultModel(): string;
}
