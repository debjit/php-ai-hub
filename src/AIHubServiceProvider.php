<![CDATA[
<?php

namespace App\AIHub;

use Illuminate\Support\ServiceProvider;

class AIHubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ai-hub.php' => config_path('ai-hub.php'),
            ], 'config');

            $this->publishes([
                __DIR__ => app_path('AIHub'),
            ], 'ai-hub-source');
        }
    }
}
]]>
