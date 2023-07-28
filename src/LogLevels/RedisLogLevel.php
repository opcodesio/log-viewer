<?php

namespace Opcodes\LogViewer\LogLevels;

class RedisLogLevel implements LevelInterface
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function from(string $value = null): LevelInterface
    {
        return new static($value);
    }

    public static function caseValues(): array
    {
        return [
            '.' => 'debug',
            '-' => 'verbose',
            '*' => 'notice',
            '#' => 'warning',
        ];
    }

    public function getName(): string
    {
        return match ($this->value) {
            '.' => 'Debug',
            '-' => 'Verbose',
            '*' => 'Notice',
            '#' => 'Warning',
            default => $this->value,
        };
    }

    public function getClass(): LevelClass
    {
        return match ($this->value) {
            '.', '-', '*' => LevelClass::info(),
            '#' => LevelClass::warning(),
            default => LevelClass::none(),
        };
    }
}
