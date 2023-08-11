<?php

namespace Opcodes\LogViewer\LogLevels;

class LaravelLogLevel implements LevelInterface
{
    const Debug = 'DEBUG';
    const Info = 'INFO';
    const Notice = 'NOTICE';
    const Warning = 'WARNING';
    const Error = 'ERROR';
    const Critical = 'CRITICAL';
    const Alert = 'ALERT';
    const Emergency = 'EMERGENCY';
    const None = '';

    public string $value;

    public function __construct(string $value = null)
    {
        $this->value = $value ?? self::None;
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
            self::None,
        ];
    }

    public static function from(string $value = null): self
    {
        return new self($value);
    }

    public function getName(): string
    {
        return match ($this->value) {
            self::None => 'None',
            default => ucfirst(strtolower($this->value)),
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
