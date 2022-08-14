<?php

namespace Opcodes\LogViewer;

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
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case None = '';

    public function getClass(): string
    {
        return match ($this) {
            self::Processed => 'success',
            self::Debug, self::Info, self::Notice, self::Processing => 'info',
            self::Warning, self::Failed => 'warning',
            self::Error, self::Critical, self::Alert, self::Emergency => 'danger',
            default => 'none',
        };
    }

    public static function caseValues(): array
    {
        return array_map(
            fn (\UnitEnum $case) => $case->value,
            self::cases()
        );
    }
}
