<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Opcodes\LogViewer\Console\Commands\GenerateDummyLogsCommand;
use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Livewire\FileList;
use Opcodes\LogViewer\Http\Livewire\LogList;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LogViewerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('log-viewer')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(GenerateDummyLogsCommand::class)
            ->hasRoute('web');
    }

    public function packageRegistered()
    {
        $this->app->bind('log-viewer', LogViewerService::class);
    }

    public function boot()
    {
        parent::boot();

        Livewire::component('log-viewer::file-list', FileList::class);
        Livewire::component('log-viewer::log-list', LogList::class);

        Event::listen(LogFileDeleted::class, function (LogFileDeleted $event) {
            LogViewer::clearFileCache();
        });

        if (! Gate::has('downloadLogFile')) {
            Gate::define('downloadLogFile', fn (mixed $user) => true);
        }

        if (! Gate::has('deleteLogFile')) {
            Gate::define('deleteLogFile', fn (mixed $user, LogFile $file) => true);
        }
    }
}
