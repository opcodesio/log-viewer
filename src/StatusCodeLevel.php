<?php

namespace Opcodes\LogViewer;

class StatusCodeLevel implements LevelInterface
{
    public function __construct(
        public string $value,
    ) {
    }

    public static function from(string $value = null): LevelInterface
    {
        return new static($value);
    }

    public static function caseValues(): array
    {
        return [];
    }

    public function getName(): string
    {
        return $this->value;
    }

    public function getClass(): string
    {
        $value = intval($this->value);

        if ($value < 300) {
            return 'success';
        } elseif ($value < 400) {
            return 'info';
        } elseif ($value < 500) {
            return 'warning';
        } elseif ($value < 600) {
            return 'danger';
        } else {
            return 'none';
        }
    }
}
