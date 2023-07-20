<?php

namespace Opcodes\LogViewer;

use Opcodes\LogViewer\Logs\BaseLog;
use Opcodes\LogViewer\Logs\HttpAccessLog;
use Opcodes\LogViewer\Logs\HttpApacheErrorLog;
use Opcodes\LogViewer\Logs\HttpNginxErrorLog;
use Opcodes\LogViewer\Logs\LaravelLog;

class LogTypeRegistrar
{
    private array $logTypes = [
        ['laravel', LaravelLog::class],
        ['http_access', HttpAccessLog::class],
        ['http_error_apache', HttpApacheErrorLog::class],
        ['http_error_nginx', HttpNginxErrorLog::class],
    ];

    public function register(string $type, string $class): void
    {
        if (!is_subclass_of($class, BaseLog::class)) {
            throw new \InvalidArgumentException("{$class} must extend " . BaseLog::class);
        }

        array_unshift($this->logTypes, [$type, $class]);
    }

    public function getClass(string $type): ?string
    {
        foreach ($this->logTypes as $logType) {
            if ($logType[0] === $type) {
                return $logType[1];
            }
        }

        return null;
    }

    public function guessTypeFromFirstLine(LogFile|string $textOrFile): ?string
    {
        if ($textOrFile instanceof LogFile) {
            $textOrFile = $textOrFile->getFirstLine();
        }

        foreach ($this->logTypes as [$type, $class]) {
            if ($class::matches($textOrFile)) {
                return $type;
            }
        }

        return null;
    }
}
