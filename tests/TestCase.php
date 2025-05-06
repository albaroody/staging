<?php

namespace Albaroody\Staging\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Albaroody\Staging\StagingServiceProvider;
use Illuminate\Support\Facades\Schema;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Albaroody\\Staging\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            StagingServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        // Optional: Set up an in-memory SQLite database for faster tests
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Load and run the migration for the test environment
        // Ensure the filename matches the one potentially renamed by configure.php
        $migration = include __DIR__.'/../database/migrations/create_staging_entries_table.php.stub';
        $migration->up();

    }
}
