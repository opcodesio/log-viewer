<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HttpNginxErrorLog extends BaseLog
{
    public static string $regex = '/^(?P<datetime>[\d+\/ :]+) \[(?P<errortype>.+)\] .*?: (?P<errormessage>.+?)(?:, client: (?P<client>.+?))?(?:, server: (?P<server>.+?))?(?:, request: "?(?P<request>.+?)"?)?(?:, host: "?(?P<host>.+?)"?)?$/';

    public static string $levelClass = Level::class;

    public function parseText(): void
    {
        $matches = [];
        preg_match(self::$regex, $this->text, $matches);

        $this->datetime = $this->parseDateTime($matches['datetime'] ?? null)?->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );
        $this->level = $matches['errortype'] ?? null;
        $this->message = $matches['errormessage'] ?? null;
        $this->context = [
            'client' => $matches['client'] ?? null,
            'server' => $matches['server'] ?? null,
            'request' => $matches['request'] ?? null,
            'host' => $matches['host'] ?? null,
        ];

        unset($matches);
    }

    public function parseDateTime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::createFromFormat('Y/m/d H:i:s', $datetime) : null;
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        $matches = [];
        $result = preg_match(static::$regex, $text, $matches) === 1;

        if ($result) {
            $timestamp = Carbon::createFromFormat('Y/m/d H:i:s', $matches['datetime'])->timestamp;
            $level = $matches['errortype'];
        }

        return $result;
    }
}
