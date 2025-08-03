<?php

declare(strict_types=1);

namespace PhpAiHub\Composer\Commands;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ResetComposerCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('ai-hub:reset')
            ->setDescription('Reset AI Hub files: clean and restore defaults from package stubs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Resetting AI Hub files...</info>');

        $projectRoot = getcwd() ?: '.';
        $fs = new Filesystem();

        $configTarget = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai-hub';
        $appTarget = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'AIHub';

        // Clean existing files
        try {
            $fs->remove([$configTarget, $appTarget]);
            $output->writeln('<comment>Cleaned existing AI Hub files.</comment>');
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to clean existing files: ' . $e->getMessage() . '</error>');
            return 1;
        }

        // Recreate defaults from stubs (shared + provider defaults)
        try {
            // Ensure directories exist
            $fs->mkdir([$configTarget, $appTarget]);

            $packageRoot = __DIR__ . '/../../..';

            // Copy shared stubs (core contracts/utilities)
            $sharedAppSrc = $packageRoot . '/stubs/shared/app';
            if (is_dir($sharedAppSrc)) {
                self::mirror($fs, $sharedAppSrc, $projectRoot . '/app');
            }

            // Copy default provider (OpenAI) app files
            $openaiAppSrc = $packageRoot . '/stubs/providers/openai/app';
            if (is_dir($openaiAppSrc)) {
                self::mirror($fs, $openaiAppSrc, $projectRoot . '/app');
            }

            // Copy default provider configs
            $openaiConfigSrc = $packageRoot . '/stubs/providers/openai/config/ai-hub';
            if (is_dir($openaiConfigSrc)) {
                self::mirror($fs, $openaiConfigSrc, $configTarget);
            }

            $output->writeln('<info>AI Hub files reset to defaults.</info>');
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to reset defaults: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }

    /**
     * Mirror a directory tree, overriding existing files.
     */
    private static function mirror(Filesystem $fs, string $originDir, string $targetDir): void
    {
        // Symfony Filesystem::mirror would be ideal but not always available in older versions with options.
        // Implement a simple recursive copy with override.
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($originDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                $fs->mkdir($targetPath);
            } else {
                $fs->copy($item->getPathname(), $targetPath, true);
            }
        }
    }
}
