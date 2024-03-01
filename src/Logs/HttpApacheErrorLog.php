<?php

namespace Opcodes\LogViewer\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogLevels\LaravelLogLevel;

class HttpApacheErrorLog extends Log
{
    public static string $name = 'HTTP Errors (Apache)';
    public static string $regex = '/\[(?<datetime>.*?)\]\s\[(?:(?<module>.*?):)?(?<level>.*?)\]\s\[pid\s(?<pid>\d*)\](?:\s\[client\s(?<client>.*?)\])?\s(?<message>.*)/';
    public static string $levelClass = LaravelLogLevel::class;

    protected function fillMatches(array $matches = []): void
    {
        $datetime = static::parseDateTime($matches['datetime'] ?? null);
        $this->datetime = $datetime?->setTimezone(LogViewer::timezone());

        $this->level = $matches['level'] ?? null;
        $this->message = $matches['message'] ?? null;

        $this->context = [
            'module' => ($matches['module'] ?? null) ?: null,
            'pid' => isset($matches['pid']) ? intval($matches['pid']) : null,
            'client' => ($matches['client'] ?? null) ?: null,
        ];
    }

    public static function parseDateTime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::createFromFormat('D M d H:i:s.u Y', $datetime) : null;
    }
}
