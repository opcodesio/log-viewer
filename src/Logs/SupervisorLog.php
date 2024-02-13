<?php

namespace Opcodes\LogViewer\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\LogLevels\SupervisorLogLevel;

class SupervisorLog extends Log
{
    public static string $name = 'Supervisor';
    public static string $regex = '/^(?<datetime>[0-9\-\s:,]+) (?<level>\w+) (?<message>.*)/';
    public static string $levelClass = SupervisorLogLevel::class;

    public static function parseDatetime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::createFromFormat('Y-m-d H:i:s,u', $datetime) : null;
    }
}
