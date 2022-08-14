<?php

namespace Opcodes\LogViewer\Events;

use Opcodes\LogViewer\LogFile;
use Illuminate\Foundation\Events\Dispatchable;

class LogFileDeleted
{
    use Dispatchable;

    public function __construct(
        public LogFile $file
    ) {}
}
