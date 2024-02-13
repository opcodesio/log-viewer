<?php

namespace Opcodes\LogViewer\LogLevels;

class LevelClass
{
    const SUCCESS = 'success';
    const NOTICE = 'notice';
    const INFO = 'info';
    const WARNING = 'warning';
    const DANGER = 'danger';
    const NONE = 'none';

    public function __construct(
        public string $value,
    ) {
    }

    public static function from(?string $value = null): LevelClass
    {
        return new static($value);
    }

    public static function caseValues(): array
    {
        return [
            static::SUCCESS,
            static::NOTICE,
            static::INFO,
            static::WARNING,
            static::DANGER,
            static::NONE,
        ];
    }

    public static function success(): static
    {
        return new static(static::SUCCESS);
    }

    public static function notice(): static
    {
        return new static(static::NOTICE);
    }

    public static function info(): static
    {
        return new static(static::INFO);
    }

    public static function warning(): static
    {
        return new static(static::WARNING);
    }

    public static function danger(): static
    {
        return new static(static::DANGER);
    }

    public static function none(): static
    {
        return new static(static::NONE);
    }
}
