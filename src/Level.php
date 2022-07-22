<?php

namespace Arukompas\BetterLogViewer;

enum Level: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Notice = 'notice';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
    case Alert = 'alert';
    case Emergency = 'emergency';
    case Processed = 'processed';
    case Failed = 'failed';

    public function getClass(): string
    {
        return match ($this) {
            self::Debug, self::Info, self::Notice, self::Processed => 'info',
            self::Warning, self::Failed => 'warning',
            self::Error, self::Critical, self::Alert, self::Emergency => 'danger',
            default => 'none',
        };
    }
}
