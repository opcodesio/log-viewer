<?php

namespace Opcodes\LogViewer\Tests\Unit\CustomLogs;

use Opcodes\LogViewer\Logs\BaseLog;

class CustomAccessLog extends BaseLog
{
    public static function setRegex(string $regex): void
    {
        static::$regex = $regex;
    }
}
