<?php

namespace Arukompas\BetterLogViewer;

use Arukompas\BetterLogViewer\Commands\BetterLogViewerCommand;
use Arukompas\BetterLogViewer\Http\Livewire\FileList;
use Closure;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class BetterLogViewerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('better-log-viewer')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigration('create_better-log-viewer_table')
            ->hasCommand(BetterLogViewerCommand::class);
    }

    public function boot()
    {
        parent::boot();

        Livewire::component('blv::file-list', FileList::class);
    }
}
