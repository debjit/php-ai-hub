<?php

declare(strict_types=1);

namespace PhpAiHub\Composer\Commands;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

final class CleanComposerCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('ai-hub:clean')
            ->setDescription('Remove all AI Hub files (config/ai-hub and app/AIHub).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Removing AI Hub files...</info>');

        $fs = new Filesystem();

        $projectRoot = getcwd() ?: '.';
        $configDir = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai-hub';
        $appAiHubDir = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'AIHub';

        try {
            $fs->remove([$configDir, $appAiHubDir]);
            $output->writeln('<info>AI Hub files removed.</info>');
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to remove files: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
