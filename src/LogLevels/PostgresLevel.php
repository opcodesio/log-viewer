<?php

namespace Opcodes\LogViewer\LogLevels;

class PostgresLevel implements LevelInterface
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
            'DEBUG',
            'INFO',
            'STATEMENT',
            'NOTICE',
            'WARNING',
            'ERROR',
            'LOG',
            'FATAL',
            'PANIC',
        ];
    }

    public function getName(): string
    {
        return ucfirst(strtolower($this->value));
    }

    public function getClass(): LevelClass
    {
        return match (strtolower($this->value)) {
            'debug', 'log', 'notice', 'statement', 'info' => LevelClass::info(),
            'warning' => LevelClass::warning(),
            'error', 'panic', 'fatal' => LevelClass::danger(),
            default => LevelClass::none(),
        };
    }
}
