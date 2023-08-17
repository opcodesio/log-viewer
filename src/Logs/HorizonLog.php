<?php

namespace Opcodes\LogViewer\Logs;

use Opcodes\LogViewer\Exceptions\SkipLineException;
use Opcodes\LogViewer\LogLevels\HorizonStatusLevel;

class HorizonLog extends Log
{
    public static string $name = 'Laravel Horizon';
    public static string $regex = '/^.*(?P<datetime>\d{4}-\d\d-\d\d \d\d:\d\d:\d\d) (?<message>\S+) \.* ?(?<duration>\d[\d\s\.\w]+)? (?P<level>\S+)\R?/m';
    public static string $levelClass = HorizonStatusLevel::class;
    public static array $columns = [
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Status', 'data_path' => 'level'],
        ['label' => 'Duration', 'data_path' => 'context.duration'],
        ['label' => 'Job', 'data_path' => 'message'],
    ];

    protected function fillMatches(array $matches = []): void
    {
        $this->datetime = $this->parseDatetime($matches['datetime'])?->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );
        $this->level = $matches['level'];
        $this->message = $matches['message'];
        $this->context = array_filter([
            'duration' => $matches['duration'] ?: null,
            'job_status' => $matches['level'],
            'job_class' => $matches['message'],
        ]);
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        return parent::matches($text, $timestamp, $level)
            || (str_contains($text, 'Horizon started successfully') && throw new SkipLineException);
    }
}
