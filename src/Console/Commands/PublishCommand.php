<?php

namespace Opcodes\LogViewer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Opcodes\LogViewer\LogViewerServiceProvider;
use Spatie\Watcher\Watch;

class PublishCommand extends Command
{
    protected $signature = 'log-viewer:publish  {--watch}';

    protected $description = 'Publish Log Viewer assets';

    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag' => 'log-viewer-assets',
            '--force' => true,
        ]);

        if ($this->option('watch')) {
            if (! class_exists(Watch::class)) {
                $this->error('Please install the spatie/file-system-watcher package to use the --watch option.');
                $this->info('Learn more at https://github.com/spatie/file-system-watcher');

                return;
            }

            $this->info('Watching for file changes... (Press CTRL+C to stop)');

            Watch::path(LogViewerServiceProvider::basePath('/public'))
                ->onAnyChange(function (string $type, string $path) {
                    if (Str::endsWith($path, 'manifest.json')) {
                        $this->call('vendor:publish', [
                            '--tag' => 'log-viewer-assets',
                            '--force' => true,
                        ]);
                    }
                })
                ->start();
        }
    }
}
