<?php

namespace Albaroody\Staging;

use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class StagingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('staging') // This will prefix config, views, etc.
            ->hasConfigFile() // Looks for config/staging.php
            ->hasMigration('create_staging_entries_table');

    }

    public function boot()
    {
        parent::boot();

        Route::macro('resourceWithStage', function ($name, $controller, array $options = []) {
            Route::post("$name/stage", [$controller, 'stage'])->name("$name.stage");
            Route::resource($name, $controller, $options);
        });
    }
}
