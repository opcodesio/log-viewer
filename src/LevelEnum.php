<?php

namespace Opcodes\LogViewer;

/**
 * Unfortunately, enums are not supported in PHP 8.0, thus
 * we are currently not using this class. It was left as
 * a reference to what was working on the PHP 8.1 build.
 */
enum LevelEnum: string
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
