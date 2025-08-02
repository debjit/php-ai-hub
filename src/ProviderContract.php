<?php

declare(strict_types=1);

namespace App\AIHub;

interface ProviderContract
{
    public function name(): string;

    public function defaultModel(): string;
}
