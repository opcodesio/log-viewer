<?php

namespace Opcodes\LogViewer;

abstract class HttpLog
{
    public function __construct(
        public string $text,
        public ?string $fileIdentifier = null,
        public ?int $filePosition = null,
    ) {
        $this->text = rtrim($this->text);
    }
}
