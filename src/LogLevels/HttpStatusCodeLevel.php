<?php

namespace Opcodes\LogViewer\LogLevels;

class HttpStatusCodeLevel implements LevelInterface
{
    public function __construct(
        public string $value,
    ) {
    }

    public static function from(?string $value = null): LevelInterface
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

    public function getClass(): LevelClass
    {
        $value = intval($this->value);

        if ($value < 250) {
            return LevelClass::notice();
        } elseif ($value < 300) {
            return LevelClass::success();
        } elseif ($value < 400) {
            return LevelClass::info();
        } elseif ($value < 500) {
            return LevelClass::warning();
        } elseif ($value < 600) {
            return LevelClass::danger();
        } else {
            return LevelClass::none();
        }
    }
}
