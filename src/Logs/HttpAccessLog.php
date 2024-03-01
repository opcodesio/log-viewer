<?php

namespace Opcodes\LogViewer\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogLevels\HttpStatusCodeLevel;

class HttpAccessLog extends Log
{
    public static string $name = 'HTTP Access';
    public static string $levelClass = HttpStatusCodeLevel::class;
    public static string $regex = '/(?P<ip>\S+) (?P<identity>\S+) (?P<remote_user>\S+) \[(?P<datetime>.+)\] "(?P<method>\S+) (?P<path>\S+) (?P<http_version>\S+)" (?P<status_code>\S+) (?P<content_length>\S+) "(?P<referrer>[^"]*)" "(?P<user_agent>[^"]*)"/';
    public static string $regexLevelKey = 'status_code';
    public static array $columns = [
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Status', 'data_path' => 'level'],
        ['label' => 'Request', 'data_path' => 'message'],
    ];

    protected function fillMatches(array $matches = []): void
    {
        $this->context = [
            'ip' => $matches['ip'] ?? null,
            'identity' => $matches['identity'] ?? null,
            'remote_user' => $matches['remote_user'] ?? null,
            'datetime' => $matches['datetime'] ?? null,
            'method' => $matches['method'] ?? null,
            'path' => $matches['path'] ?? null,
            'http_version' => $matches['http_version'] ?? null,
            'status_code' => isset($matches['status_code']) ? intval($matches['status_code']) : null,
            'content_length' => isset($matches['content_length']) ? intval($matches['content_length']) : null,
            'referrer' => $matches['referrer'] ?? null,
            'user_agent' => $matches['user_agent'] ?? null,
        ];

        $datetime = static::parseDateTime($matches['datetime'] ?? null);
        $this->datetime = $datetime?->setTimezone(LogViewer::timezone());

        $this->level = $matches['status_code'] ?? null;
        $this->message = sprintf(
            '%s %s',
            $this->context['method'],
            $this->context['path'],
        );
    }

    public static function parseDatetime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::createFromFormat('d/M/Y:H:i:s O', $datetime) : null;
    }
}
