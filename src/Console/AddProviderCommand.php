<?php

declare(strict_types=1);

namespace PhpAiHub\Package\Console;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class AddProviderCommand extends BaseCommand
{
    protected static $defaultName = 'ai-hub:add';
    protected static $defaultDescription = 'Install/copy an AI provider\'s source code into your Laravel app (like shadcn).';

    protected function configure(): void
    {
        $this
            ->addArgument('provider', InputArgument::REQUIRED, 'Provider name (e.g. openai, anthropic)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files')
            ->addOption('tests', 't', InputOption::VALUE_NONE, 'Copy tests if present in stubs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $provider = strtolower((string) $input->getArgument('provider'));
        $force = (bool) $input->getOption('force');
        $withTests = (bool) $input->getOption('tests');

        $io = $this->getIO();
        $fs = new Filesystem();

        // Locate package root (this package)
        $packageRoot = dirname(__DIR__, 2); // src/Console -> src -> package root
        $stubsDir = Path::join($packageRoot, 'stubs', 'providers', $provider);

        if (!$fs->exists($stubsDir)) {
            $io->writeError("<error>Unknown provider '{$provider}'.</error>");
            $io->writeError("Expected directory: {$stubsDir}");
            return self::FAILURE;
        }

        // Determine project root (where composer is executed)
        $projectRoot = getcwd() ?: '.';

        // Destinations
        $appAiHub = Path::join($projectRoot, 'app', 'AIHub');
        $configDir = Path::join($projectRoot, 'config', 'ai-hub');
        $testsDir = Path::join($projectRoot, 'tests');

        // Ensure directories
        $fs->mkdir([$appAiHub, $configDir]);

        // Copy common files if exist (shared between providers)
        $this->copyIfExists($fs, Path::join($packageRoot, 'stubs', 'shared', 'app', 'AIHub'), $appAiHub, $force, $io);
        $this->copyIfExists($fs, Path::join($packageRoot, 'stubs', 'shared', 'config', 'ai-hub'), $configDir, $force, $io);

        // Copy provider files
        $this->copyIfExists($fs, Path::join($stubsDir, 'app', 'AIHub'), $appAiHub, $force, $io);
        $this->copyIfExists($fs, Path::join($stubsDir, 'config', 'ai-hub'), $configDir, $force, $io);

        // Copy tests optionally
        if ($withTests) {
            $this->copyIfExists($fs, Path::join($stubsDir, 'tests'), $testsDir, $force, $io);
        }

        // Registry
        $this->updateRegistry(Path::join($projectRoot, 'ai-hub.json'), $provider, $io);

        $io->write("<info>Provider '{$provider}' installed into your app.</info>");
        $io->write("Location: app/AIHub and config/ai-hub");
        return self::SUCCESS;
    }

    private function copyIfExists(Filesystem $fs, string $from, string $to, bool $force, $io): void
    {
        if (!$fs->exists($from)) {
            return;
        }

        // Mirror with options; if $force is false, skip overwriting existing files
        $fs->mirror($from, $to, null, [
            'override' => $force,
            'copy_on_windows' => true,
            'delete' => false,
        ]);

        $io->write("Copied: {$from} -> {$to}" . ($force ? ' (force)' : ''));
    }

    private function updateRegistry(string $registryPath, string $provider, $io): void
    {
        $data = ['providers' => []];
        if (is_file($registryPath)) {
            $decoded = json_decode((string) file_get_contents($registryPath), true);
            if (is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }

        $data['providers'][$provider] = [
            'installed_at' => date('c'),
        ];

        file_put_contents($registryPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $io->write("Updated registry: {$registryPath}");
    }
}
