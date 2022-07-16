<?php

namespace Arukompas\BetterLogViewer;

use Arukompas\BetterLogViewer\Commands\BetterLogViewerCommand;
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
            ->hasMigration('create_better-log-viewer_table')
            ->hasCommand(BetterLogViewerCommand::class);
    }
}
