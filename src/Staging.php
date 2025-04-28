<?php

namespace Albaroody\Staging;

use Illuminate\Support\ServiceProvider;

class StagingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/staging.php', 'staging');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/staging.php' => config_path('staging.php'),
        ], 'staging-config');

        if (! class_exists('CreateStagingEntriesTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_staging_entries_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_staging_entries_table.php'),
            ], 'staging-migrations');
        }
    }
}
