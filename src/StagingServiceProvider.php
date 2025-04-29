<?php

namespace Albaroody\Staging;

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
}
