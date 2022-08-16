<?php

namespace Opcodes\LogViewer;

class Level
{
    const Debug = 'debug';

    const Info = 'info';

    const Notice = 'notice';

    const Warning = 'warning';

    const Error = 'error';

    const Critical = 'critical';

    const Alert = 'alert';

    const Emergency = 'emergency';

    const Processing = 'processing';

    const Processed = 'processed';

    const Failed = 'failed';

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
            self::Processing,
            self::Processed,
            self::Failed,
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
            default => ucfirst($this->value),
        };
    }

    public function getClass(): string
    {
        return match ($this->value) {
            self::Processed => 'success',
            self::Debug, self::Info, self::Notice, self::Processing => 'info',
            self::Warning, self::Failed => 'warning',
            self::Error, self::Critical, self::Alert, self::Emergency => 'danger',
            default => 'none',
        };
    }

    public static function caseValues(): array
    {
        return self::cases();
    }
}
