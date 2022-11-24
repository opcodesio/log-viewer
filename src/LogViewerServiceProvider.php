<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Opcodes\LogViewer\Console\Commands\GenerateDummyLogsCommand;
use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Livewire\FileList;
use Opcodes\LogViewer\Http\Livewire\LogList;

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
        $this->app->singleton(PreferenceStore::class, PreferenceStore::class);
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

        // registering routes
        $this->loadRoutesFrom($this->basePath('/routes/web.php'));

        // registering views
        $this->loadViewsFrom($this->basePath('/resources/views'), $this->name);

        Livewire::component('log-viewer::file-list', FileList::class);
        Livewire::component('log-viewer::log-list', LogList::class);

        Event::listen(LogFileDeleted::class, function (LogFileDeleted $event) {
            LogViewer::clearFileCache();
        });

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
