<?php

declare(strict_types=1);

namespace PhpAiHub\Package\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use PhpAiHub\Package\Console\AddProviderCommand;

final class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [
            new AddProviderCommand(),
        ];
    }
}
