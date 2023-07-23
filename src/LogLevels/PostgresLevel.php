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
            'log', 'notice', 'statement', 'info', 'context', 'detail', 'hint', 'query',
            'debug', 'debug1', 'debug2', 'debug3', 'debug4', 'debug5' => LevelClass::info(),
            'warning' => LevelClass::warning(),
            'error', 'panic', 'fatal' => LevelClass::danger(),
            default => LevelClass::none(),
        };
    }
}
