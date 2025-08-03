<?php

declare(strict_types=1);

namespace PhpAiHub\Composer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use PhpAiHub\Composer\Commands\CleanComposerCommand;
use PhpAiHub\Composer\Commands\ResetComposerCommand;

final class CommandProvider implements CommandProviderCapability
{
    public function getCommands(): array
    {
        return [
            new CleanComposerCommand(),
            new ResetComposerCommand(),
        ];
    }
}
