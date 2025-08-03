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
        $this->info('Removing AI Hub files...');

        $filesystem = new Filesystem();

        $filesystem->remove(config_path('ai-hub'));
        $filesystem->remove(app_path('AIHub'));

        $this->info('AI Hub files removed.');
    }
}
