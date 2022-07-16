<?php

namespace Arukompas\BetterLogViewer;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Arukompas\BetterLogViewer\Commands\BetterLogViewerCommand;

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
