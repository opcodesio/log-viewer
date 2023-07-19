<?php

namespace Opcodes\LogViewer;

use Opcodes\LogViewer\Logs\HttpAccessLog;
use Opcodes\LogViewer\Logs\HttpApacheErrorLog;
use Opcodes\LogViewer\Logs\HttpNginxErrorLog;
use Opcodes\LogViewer\Logs\LaravelLog;

class LogTypeRegistrar
{
    private static array $logTypes = [
        'laravel' => LaravelLog::class,
        'http_access' => HttpAccessLog::class,
        'http_error_apache' => HttpApacheErrorLog::class,
        'http_error_nginx' => HttpNginxErrorLog::class,
    ];

    public static function register(string $type, string $class): void
    {
        static::$logTypes[$type] = $class;
    }

    public static function getClass(string $type): string
    {
        return static::$logTypes[$type];
    }

    public static function guessTypeFromFirstLine(LogFile|string $textOrFile): ?string
    {
        if ($textOrFile instanceof LogFile) {
            $textOrFile = $textOrFile->getFirstLine();
        }

        foreach (static::$logTypes as $type => $class) {
            if ($class::matches($textOrFile)) {
                return $type;
            }
        }

        return null;
    }
}
