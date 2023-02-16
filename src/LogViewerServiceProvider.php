<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Console\Commands\GenerateDummyLogsCommand;
use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Facades\LogViewer;

class LogViewerServiceProvider extends ServiceProvider
{
    private string $name = 'log-viewer';

    public function register()
    {
        $this->mergeConfigFrom($this->basePath("/config/{$this->name}.php"), $this->name);

        $this->app->bind('log-viewer', LogViewerService::class);
        $this->app->bind('log-viewer-cache', function () {
            return Cache::driver(config('log-viewer.cache_driver'));
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // publishing the config
            $this->publishes([
                $this->basePath("/config/{$this->name}.php") => config_path("{$this->name}.php"),
            ], "{$this->name}-config");

            // registering the command
            $this->commands([GenerateDummyLogsCommand::class]);
        }

        if (! $this->isEnabled()) {
            return;
        }

        $this->registerRoutes();
        $this->registerResources();
        $this->defineAssetPublishing();
        $this->defineDefaultGates();

        Event::listen(LogFileDeleted::class, function (LogFileDeleted $event) {
            LogViewer::clearFileCache();
        });
    }

    /**
     * Register the Log Viewer routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group([
            'domain' => config('log-viewer.route_domain', null),
            'prefix' => config('log-viewer.route_path'),
            'namespace' => 'Opcodes\LogViewer\Http\Controllers',
            'middleware' => config('log-viewer.middleware', 'web'),
        ], function () {
            $this->loadRoutesFrom($this->basePath('/routes/web.php'));
        });
    }

    protected function registerResources()
    {
        $this->loadViewsFrom($this->basePath('/resources/views'), 'log-viewer');
    }

    protected function defineAssetPublishing()
    {
        $this->publishes([
            $this->basePath('/public') => public_path('vendor/log-viewer'),
        ], ['log-viewer-assets', 'laravel-assets']);
    }

    protected function defineDefaultGates()
    {
        if (! Gate::has('downloadLogFile')) {
            Gate::define('downloadLogFile', fn (mixed $user, LogFile $file) => true);
        }

        if (! Gate::has('downloadLogFolder')) {
            Gate::define('downloadLogFolder', fn (mixed $user, LogFolder $folder) => true);
        }

        if (! Gate::has('deleteLogFile')) {
            Gate::define('deleteLogFile', fn (mixed $user, LogFile $file) => true);
        }

        if (! Gate::has('deleteLogFolder')) {
            Gate::define('deleteLogFolder', fn (mixed $user, LogFolder $folder) => true);
        }
    }

    private function basePath(string $path): string
    {
        return __DIR__.'/..'.$path;
    }

    private function isEnabled(): bool
    {
        return (bool) $this->app['config']->get("{$this->name}.enabled", true);
    }
}
