<?php

namespace Opcodes\LogViewer;

interface LogInterface
{
    public function __construct(
        string $text,
        string $fileIdentifier = null,
        int $filePosition = null,
        int $index = null,
    );

    public function getTimestamp(): int;

    public function getLevel(): LevelInterface;

    public static function matches(string $text): bool;

    public static function isMultiline(): bool;

    public static function levelClass(): string;
}
