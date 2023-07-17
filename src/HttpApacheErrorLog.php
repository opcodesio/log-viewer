<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HttpApacheErrorLog extends BaseLog
{
    public static string $regex = '/\[(?<dttm>.*?)\]\s\[(?:(?<module>.*?):)?(?<level>.*?)\]\s\[pid\s(?<pid>\d*)\](?:\s\[client\s(?<client>.*?)\])?\s(?<message>.*)/';

    public static string $levelClass = Level::class;

    public function parseText(): void
    {
        $matches = [];
        preg_match(self::$regex, $this->text, $matches);

        $this->datetime = $this->parseDateTime($matches['dttm'] ?? null)?->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );
        $this->level = $matches['level'] ?? null;
        $this->message = $matches['message'] ?? null;

        $this->context = [
            'module' => ($matches['module'] ?? null) ?: null,
            'pid' => isset($matches['pid']) ? intval($matches['pid']) : null,
            'client' => ($matches['client'] ?? null) ?: null,
        ];

        unset($matches);
    }

    public function parseDateTime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::createFromFormat('D M d H:i:s.u Y', $datetime) : null;
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        $matches = [];
        $result = preg_match(static::$regex, $text, $matches) === 1;

        if ($result) {
            $timestamp = Carbon::createFromFormat('D M d H:i:s.u Y', $matches['dttm'])->timestamp;
            $level = $matches['level'];
        }

        return $result;
    }
}
