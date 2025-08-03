<?php

namespace PhpAiHub\Console;

use Illuminate\Console\Command;
use Symfony\Component\Filesystem\Filesystem;

class CleanCommand extends Command
{
    protected $signature = 'ai-hub:clean';

    protected $description = 'Remove all AI Hub files.';

    public function handle()
    {
        $this->warn('This will permanently remove:');
        $this->line('- config/ai-hub directory');
        $this->line('- app/AIHub directory');
        $this->line('- ai-hub.json file (if present)');
        $this->newLine();

        if (! $this->confirm('Do you really want to proceed?', false)) {
            $this->info('Clean cancelled.');
            return Command::SUCCESS;
        }

        $this->info('Removing AI Hub files...');

        $filesystem = new Filesystem();

        $filesystem->remove(config_path('ai-hub'));
        $filesystem->remove(app_path('AIHub'));

        // Also remove ai-hub.json from project root if present
        $projectRoot = base_path();
        $aiHubJson = $projectRoot . DIRECTORY_SEPARATOR . 'ai-hub.json';
        if ($filesystem->exists($aiHubJson)) {
            $filesystem->remove($aiHubJson);
        }

        $this->info('AI Hub files removed.');

        return Command::SUCCESS;
    }
}
