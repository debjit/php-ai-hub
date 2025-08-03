<?php

namespace PhpAiHub\Console;

use Illuminate\Console\Command;

class ResetCommand extends Command
{
    protected $signature = 'ai-hub:reset';

    protected $description = 'Reset the AI Hub files to the default state.';

    public function handle()
    {
        $this->info('Resetting AI Hub files...');

        $this->call('ai-hub:clean');
        $this->call('vendor:publish', [
            '--tag' => 'ai-hub-config',
            '--force' => true,
        ]);

        $this->info('AI Hub files reset.');
    }
}
