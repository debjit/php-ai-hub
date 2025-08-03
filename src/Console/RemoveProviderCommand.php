<?php

declare(strict_types=1);

namespace PhpAiHub\Console;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class RemoveProviderCommand extends BaseCommand
{
    protected static $defaultName = 'ai-hub:remove';
    protected static $defaultDescription = 'Remove an AI provider\'s published files from your Laravel app.';

    protected function configure(): void
    {
        $this
            ->addArgument('provider', InputArgument::REQUIRED, 'Provider name (e.g. openai)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $provider = strtolower((string) $input->getArgument('provider'));
        $force = (bool) $input->getOption('force');

        $io = $this->getIO();
        $fs = new Filesystem();

        // Determine project root (where composer is executed)
        $projectRoot = getcwd() ?: '.';

        // Target locations
        $appAiHub = Path::join($projectRoot, 'app', 'AIHub');
        $configDir = Path::join($projectRoot, 'config', 'ai-hub');
        $registryPath = Path::join($projectRoot, 'ai-hub.json');

        // Compute provider-specific files that our stubs add
        // We only remove provider-specific files and configs.
        // We DO NOT remove shared config (config/ai-hub/ai-hub.php) automatically.
        $filesToRemove = [];

        // Provider-specific config
        $filesToRemove[] = Path::join($configDir, $provider . '.php');

        // Provider-specific app files (based on stubs present in this package)
        // We inspect our own stubs to figure out what to remove so future providers work automatically.
        $packageRoot = dirname(__DIR__, 2); // src/Console -> src -> package root
        $providerStubApp = Path::join($packageRoot, 'stubs', 'providers', $provider, 'app', 'AIHub');
        if ($fs->exists($providerStubApp)) {
            // Mirror directory layout to discover file list
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($providerStubApp, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $stubItem) {
                if ($stubItem->isFile()) {
                    $relative = substr($stubItem->getPathname(), strlen($providerStubApp) + 1);
                    $filesToRemove[] = Path::join($appAiHub, $relative);
                }
            }
        } else {
            // Fallback to common expected names for known providers
            if ($provider === 'openai') {
                $filesToRemove[] = Path::join($appAiHub, 'OpenAIProvider.php');
                $filesToRemove[] = Path::join($appAiHub, 'ChatClient.php');
                $filesToRemove[] = Path::join($appAiHub, 'Connectors', 'HttpConnector.php');
            }
        }

        // Filter duplicates
        $filesToRemove = array_values(array_unique($filesToRemove));

        if (!$force) {
            $io->write("<question>This will remove the following files if they exist:</question>");
            foreach ($filesToRemove as $file) {
                $io->write(" - {$this->relPath($projectRoot, $file)}");
            }
            $io->write("<comment>Shared file config/ai-hub/ai-hub.php will NOT be removed.</comment>");
            $io->write("<question>Proceed? [y/N]</question>");
            // Composer IO does not provide a prompt API on BaseCommand; read from STDIN as a fallback.
            $answer = trim((string) fgets(STDIN));
            if (!in_array(strtolower($answer), ['y', 'yes'], true)) {
                $io->write("<comment>Aborted.</comment>");
                return self::SUCCESS;
            }
        }

        // Remove files
        $removed = [];
        foreach ($filesToRemove as $file) {
            if ($fs->exists($file)) {
                $fs->remove($file);
                $removed[] = $file;
                $io->write("Removed: " . $this->relPath($projectRoot, $file));
            }
        }

        // Clean up any now-empty directories inside app/AIHub and config/ai-hub
        $this->cleanupEmptyDirs($fs, $appAiHub, $io, $projectRoot);
        $this->cleanupEmptyDirs($fs, $configDir, $io, $projectRoot);

        // Update registry file
        $this->updateRegistryRemove($registryPath, $provider, $io);

        if (empty($removed)) {
            $io->write("<comment>No provider files found to remove for '{$provider}'.</comment>");
        } else {
            $io->write("<info>Provider '{$provider}' files removed.</info>");
        }

        return self::SUCCESS;
    }

    private function cleanupEmptyDirs(Filesystem $fs, string $root, $io, string $projectRoot): void
    {
        if (!$fs->exists($root)) {
            return;
        }

        // Walk directories bottom-up to remove empties
        $dirs = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $dirs[] = $item->getPathname();
            }
        }

        foreach ($dirs as $dir) {
            // If directory is empty after removals, remove it
            if ($this->isDirEmpty($dir)) {
                $fs->remove($dir);
                $io->write("Removed empty dir: " . $this->relPath($projectRoot, $dir));
            }
        }

        // Finally remove root if empty
        if ($this->isDirEmpty($root)) {
            $fs->remove($root);
            $io->write("Removed empty dir: " . $this->relPath($projectRoot, $root));
        }
    }

    private function isDirEmpty(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $files = scandir($dir);
        if ($files === false) {
            return false;
        }
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..') {
                return false;
            }
        }
        return true;
    }

    private function relPath(string $base, string $path): string
    {
        // Provide a nicer relative path in output if inside the project
        if (str_starts_with($path, $base)) {
            $rel = ltrim(substr($path, strlen($base)), DIRECTORY_SEPARATOR);
            return $rel === '' ? '.' : $rel;
        }
        return $path;
    }

    private function updateRegistryRemove(string $registryPath, string $provider, $io): void
    {
        if (!is_file($registryPath)) {
            return;
        }

        $decoded = json_decode((string) file_get_contents($registryPath), true);
        if (!is_array($decoded)) {
            return;
        }

        if (isset($decoded['providers'][$provider])) {
            unset($decoded['providers'][$provider]);
            file_put_contents($registryPath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->write("Updated registry: {$registryPath} (removed '{$provider}')");
        }
    }
}
