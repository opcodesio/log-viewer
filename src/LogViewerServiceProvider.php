<?php

namespace Opcodes\LogViewer;

use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Http\Livewire\FileList;
use Opcodes\LogViewer\Http\Livewire\LogList;
use Closure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
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
            ->hasRoute('web');
    }

    public function packageRegistered()
    {
        $this->app->bind('log-viewer', LogViewer::class);
    }

    public function boot()
    {
        parent::boot();

        Livewire::component('log-viewer::file-list', FileList::class);
        Livewire::component('log-viewer::log-list', LogList::class);

        Event::listen(LogFileDeleted::class, function () {
            \Opcodes\LogViewer\Facades\LogViewer::clearFileCache();
        });
    }
}
