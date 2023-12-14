<?php

namespace Opcodes\LogViewer\LogLevels;

class NginxStatusLevel implements LevelInterface
{
    const Debug = 'debug';
    const Info = 'info';
    const Notice = 'notice';
    const Warning = 'warn';
    const Error = 'error';
    const Critical = 'crit';
    const Alert = 'alert';
    const Emergency = 'emerg';

    public string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value ?? self::Error;
    }

    public static function cases(): array
    {
        return [
            self::Debug,
            self::Info,
            self::Notice,
            self::Warning,
            self::Error,
            self::Critical,
            self::Alert,
            self::Emergency,
        ];
    }

    public static function from(?string $value = null): self
    {
        return new self($value);
    }

    public function getName(): string
    {
        return match ($this->value) {
            self::Warning => 'Warning',
            self::Critical => 'Critical',
            self::Emergency => 'Emergency',
            default => ucfirst($this->value),
        };
    }

    public function getClass(): LevelClass
    {
        return match ($this->value) {
            self::Debug, self::Info, self::Notice => LevelClass::info(),
            self::Warning => LevelClass::warning(),
            self::Error, self::Critical, self::Alert, self::Emergency => LevelClass::danger(),
            default => LevelClass::none(),
        };
    }

    public static function caseValues(): array
    {
        return self::cases();
    }
}
