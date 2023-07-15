<?php

namespace Opcodes\LogViewer;

abstract class HttpLog implements LogInterface
{
    public function __construct(
        public string $text,
        public ?string $fileIdentifier = null,
        public ?int $filePosition = null,
        public ?int $index = null,
    ) {
        $this->text = rtrim($this->text);
    }

    public function getTimestamp(): int
    {
        return $this->datetime?->timestamp ?? 0;
    }

    public function getLevel(): LevelInterface
    {
        $levelClass = static::levelClass();

        /** @noinspection PhpUndefinedMethodInspection */
        return $levelClass::from($this->level);
    }

    public static function isMultiline(): bool
    {
        return false;
    }

    public function url(): string
    {
        return route('log-viewer.index', ['file' => $this->fileIdentifier, 'query' => 'log-index:'.$this->index]);
    }
}
