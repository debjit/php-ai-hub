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
        $output->writeln('<comment>This will permanently remove:</comment>');
        $output->writeln('- config/ai-hub directory');
        $output->writeln('- app/AIHub directory');
        $output->writeln('- ai-hub.json file (if present)');
        $output->writeln('');

        // Simple confirmation prompt using STDIN since we are inside composer command
        $output->writeln('<question>Do you really want to proceed? [y/N]</question>');
        $confirm = strtolower(trim((string) fgets(STDIN)));
        if ($confirm !== 'y' && $confirm !== 'yes') {
            $output->writeln('<info>Clean cancelled.</info>');
            return 0;
        }

        $output->writeln('<info>Removing AI Hub files...</info>');

        $fs = new Filesystem();

        $projectRoot = getcwd() ?: '.';
        $configDir = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai-hub';
        $appAiHubDir = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'AIHub';
        $aiHubJson = $projectRoot . DIRECTORY_SEPARATOR . 'ai-hub.json';

        try {
            $paths = [$configDir, $appAiHubDir];
            if ($fs->exists($aiHubJson)) {
                $paths[] = $aiHubJson;
            }
            $fs->remove($paths);
            $output->writeln('<info>AI Hub files removed.</info>');
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to remove files: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
