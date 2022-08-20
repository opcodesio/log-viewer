<?php

namespace Opcodes\LogViewer\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Livewire\LivewireServiceProvider;
use Opcodes\LogViewer\LogViewerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Opcodes\\LogViewer\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            LogViewerServiceProvider::class,
            RouteServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:yTtQNlEOB1IqYydLG9Z5pKRSxhZffdOxT1iuZIJi+eM=');

        /*
        $migration = include __DIR__.'/../database/migrations/create_log-viewer_table.php.stub';
        $migration->up();
        */
    }
}
