<?php

namespace Arukompas\BetterLogViewer\Commands;

use Illuminate\Console\Command;

class BetterLogViewerCommand extends Command
{
    public $signature = 'better-log-viewer';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
