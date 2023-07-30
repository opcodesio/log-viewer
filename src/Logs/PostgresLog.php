<?php

namespace Opcodes\LogViewer\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\LogLevels\PostgresLevel;

class PostgresLog extends Log
{
    public static string $name = 'Postgres';
    public static string $regex = '/^(?<datetime>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3} \w+) \[(?<pid>\d+)\] (?<level>\w+):\s*(?<message>.*)?$/m';
    public static string $levelClass = PostgresLevel::class;

    protected function fillMatches(array $matches = []): void
    {
        parent::fillMatches($matches);

        $this->context['pid'] = (int) $matches['pid'] ?? 0;
    }

    public static function parseDatetime(?string $datetime): ?CarbonInterface
    {
        return isset($datetime) ? Carbon::createFromFormat('Y-m-d H:i:s.u T', $datetime) : null;
    }
}
