<?php

declare(strict_types=1);

namespace PhpAiHub\Composer\Commands;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Composer command wrapper for ai-hub:remove.
 * Reuses the existing implementation in PhpAiHub\Console\RemoveProviderCommand.
 */
final class RemoveComposerCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('ai-hub:remove')
            ->setDescription("Remove an AI provider's published files from your Laravel app.")
            ->addArgument('provider', InputArgument::REQUIRED, 'Provider name (e.g. openai)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Delegate to the actual implementation so behavior stays in one place.
        $impl = new \PhpAiHub\Console\RemoveProviderCommand();
        // Ensure IO is available to the delegated command
        $impl->setIO($this->getIO());

        return $impl->run($input, $output);
    }
}
