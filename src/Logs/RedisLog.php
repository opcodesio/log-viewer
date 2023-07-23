<?php

namespace Opcodes\LogViewer\Logs;

class RedisLog extends BaseLog
{
    public static string $regex = '/^(?<pid>\d+):(?<role_letter>\w) (?<datetime>.*) (?<level>[.\-*#]) (?<message>.*)/';

    public static array $columns = [
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'PID', 'data_path' => 'context.pid'],
        ['label' => 'Role', 'data_path' => 'context.role'],
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    protected function parseText(array &$matches = []): void
    {
        parent::parseText($matches);

        $matches['level'] = match ($matches['level']) {
            '.' => 'debug',
            '-' => 'verbose',
            '*' => 'notice',
            '#' => 'warning',
            default => $matches['level'],
        };
    }

    protected function fillMatches(array $matches = []): void
    {
        parent::fillMatches($matches);

        $this->context = [
            'pid' => intval($matches['pid'] ?? null),
            'role' => $matches['role_letter'],
            'role_description' => match ($matches['role_letter'] ?? null) {
                'X' => 'sentinel',
                'S' => 'slave',
                'M' => 'master',
                'C' => 'RDB/AOF writing child',
                default => null,
            },
        ];
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        $result = parent::matches($text, $timestamp, $level);

        if ($result) {
            $level = match ($level) {
                '.' => 'debug',
                '-' => 'verbose',
                '*' => 'notice',
                '#' => 'warning',
                default => $level,
            };
        }

        return $result;
    }
}
