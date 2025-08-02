<?php

declare(strict_types=1);

namespace PhpAiHub\Base;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class Installer implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        // No-op: We use static event subscribers.
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // No-op
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // No-op
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'post-install-cmd' => 'handleComposerEvent',
            'post-update-cmd'  => 'handleComposerEvent',
        ];
    }

    public static function handleComposerEvent(Event $event): void
    {
        $io = $event->getIO();
        $composer = $event->getComposer();
        $package = $composer->getPackage();

        $extra = $package->getExtra()['ai-sdk'] ?? [];
        // Ensure config files are published for Laravel usage
        self::publishConfigsIfMissing($io);
        $providers = $extra['providers'] ?? [];
        $targetBase = $extra['target'] ?? 'app/AI/Providers';

        // Parse args like --add=openai or --remove=anthropic
        $args = method_exists($event, 'getArguments') ? $event->getArguments() : [];
        $addArg = $args['--add'] ?? null;
        $removeArg = $args['--remove'] ?? null;

        $projectRoot = getcwd() ?: dirname(__DIR__, 2);
        $fs = new Filesystem();
        $registry = new Registry($projectRoot . DIRECTORY_SEPARATOR . 'ai-sdk.json');

        if ($addArg) {
            $key = strtolower(trim((string)$addArg));
            if (!isset($providers[$key])) {
                $io->writeError("⚠ Unknown AI provider key: {$key}");
                return;
            }

            $source = __DIR__ . DIRECTORY_SEPARATOR . 'Stubs' . DIRECTORY_SEPARATOR . ucfirst($key);
            $target = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetBase) . DIRECTORY_SEPARATOR . ucfirst($key);

            if (!is_dir($source)) {
                $io->writeError("⚠ Missing stub source for provider '{$key}' at: {$source}");
                return;
            }

            $fs->mkdir($target);
            $fs->mirror($source, $target);

            $registry->addProvider($key, $target);

            $io->write("✅ Installed AI provider: {$key}");
            $io->write("→ From: {$source}");
            $io->write("→ To:   {$target}");
        }

        if ($removeArg) {
            $key = strtolower(trim((string)$removeArg));
            if (!$registry->hasProvider($key)) {
                $io->writeError("⚠ Provider '{$key}' is not registered.");
                return;
            }

            $path = $registry->getProviderPath($key);
            if ($fs->exists($path)) {
                $fs->remove($path);
            }

            $registry->removeProvider($key);
            $io->write("🗑 Removed AI provider: {$key}");
        }
    }

    /**
     * Publish the package config into the host application's config directory if missing.
     * This is a simple copy to config/ai-hub.php to align with Laravel conventions.
     */
    private static function publishConfigsIfMissing(IOInterface $io): void
    {
        $projectRoot = getcwd() ?: dirname(__DIR__, 2);
        $packageConfigRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config';

        // 1) Publish main config/ai-hub.php
        $appConfigDir = $projectRoot . DIRECTORY_SEPARATOR . 'config';
        $mainDest = $appConfigDir . DIRECTORY_SEPARATOR . 'ai-hub.php';
        $mainSource = $packageConfigRoot . DIRECTORY_SEPARATOR . 'ai-hub.php';

        if (!is_dir($appConfigDir)) {
            @mkdir($appConfigDir, 0777, true);
        }

        if (!file_exists($mainDest) && file_exists($mainSource)) {
            @copy($mainSource, $mainDest);
            $io->write('<info>Published config: config/ai-hub.php</info>');
        }

        // 2) Publish provider configs into config/ai-hub/
        $appAiHubDir = $appConfigDir . DIRECTORY_SEPARATOR . 'ai-hub';
        if (!is_dir($appAiHubDir)) {
            @mkdir($appAiHubDir, 0777, true);
        }

        $providers = ['openai', 'anthropic'];
        foreach ($providers as $provider) {
            $src = $packageConfigRoot . DIRECTORY_SEPARATOR . 'ai-hub' . DIRECTORY_SEPARATOR . $provider . '.php';
            $dst = $appAiHubDir . DIRECTORY_SEPARATOR . $provider . '.php';
            if (file_exists($src) && !file_exists($dst)) {
                @copy($src, $dst);
                $io->write('<info>Published config: config/ai-hub/' . $provider . '.php</info>');
            }
        }
    }
}
