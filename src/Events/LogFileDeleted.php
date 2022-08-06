<?php

namespace Arukompas\BetterLogViewer\Events;

use Arukompas\BetterLogViewer\LogFile;
use Illuminate\Foundation\Events\Dispatchable;

class LogFileDeleted
{
    use Dispatchable;

    public function __construct(
        public LogFile $file
    ) {}
}
