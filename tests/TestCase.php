<?php

namespace Albaroody\Staging\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Albaroody\Staging\StagingServiceProvider;

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

            
        $stubPath = __DIR__ . '/../database/migrations/create_staging_entries_table.php.stub';
        
        if (file_exists($stubPath)) {

            $temporaryMigrationPath = __DIR__ . '/temp_create_staging_entries_table.php';
            
            file_put_contents($temporaryMigrationPath, str_replace(
                'new class() extends Migration {',
                'new class extends \Illuminate\Database\Migrations\Migration {',
                file_get_contents($stubPath)
            ));


            $migration = include $temporaryMigrationPath;

            if ($migration) {
            } else {
            }

            if ($migration && method_exists($migration, 'up')) {
                $migration->up();
            } else {
            }

            unlink($temporaryMigrationPath);
        } else {
        }
    }
}
