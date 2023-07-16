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

    /**
     * @param  string  $text The log entry line.
     * @param  int|null  $timestamp The timestamp (as integer) extracted from the log entry. Used for indexing.
     * @param  string|null  $level The log level (severity) extracted from the log entry. Used for indexing.
     */
    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool;

    public static function levelClass(): string;
}
