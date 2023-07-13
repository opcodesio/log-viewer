<?php

namespace Opcodes\LogViewer;

abstract class HttpLog
{
    public function __construct(
        public string $text,
        public ?string $fileIdentifier = null,
        public ?int $filePosition = null,
        public ?int $index = null,
    ) {
        $this->text = rtrim($this->text);
    }

    public static function matches(string $text): bool
    {
        return preg_match(static::$regex, $text) === 1;
    }

    public function url(): string
    {
        return route('log-viewer.index', ['file' => $this->fileIdentifier, 'query' => 'file-pos:'.$this->filePosition]);
    }
}
