<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HttpAccessLog extends BaseLog
{
    public static string $levelClass = StatusCodeLevel::class;

    public static string $regex = '/(\S+) (\S+) (\S+) \[(.+)\] "(\S+) (\S+) (\S+)" (\S+) (\S+) "([^"]*)" "([^"]*)"/';

    protected function parseText(): void
    {
        $matches = [];
        preg_match(self::$regex, $this->text, $matches);

        $this->context = [
            'ip' => $matches[1] ?? null,
            'identity' => $matches[2] ?? null,
            'remoteUser' => $matches[3] ?? null,
            'datetime' => $matches[4] ?? null,
            'method' => $matches[5] ?? null,
            'path' => $matches[6] ?? null,
            'httpVersion' => $matches[7] ?? null,
            'statusCode' => isset($matches[8]) ? intval($matches[8]) : null,
            'contentLength' => isset($matches[9]) ? intval($matches[9]) : null,
            'referrer' => $matches[10] ?? null,
            'userAgent' => $matches[11] ?? null,
        ];

        $this->datetime = $this->parseDateTime($matches[4] ?? null)?->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );
        $this->level = $matches[8] ?? null;
        $this->message = sprintf(
            '%s %s',
            $this->context['method'],
            $this->context['path'],
        );

        unset($matches);
    }

    protected function parseDateTime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::parse($datetime) : null;
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        $matches = [];
        $result = preg_match(static::$regex, $text, $matches) === 1;

        if ($result) {
            $timestamp = strtotime($matches[4]);
            $level = $matches[8];
        }

        return $result;
    }

    public static function levelClass(): string
    {
        return static::$levelClass;
    }
}
