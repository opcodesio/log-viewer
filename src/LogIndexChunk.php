<?php

namespace Opcodes\LogViewer;

class LogIndexChunk
{
    public function __construct(
        public int $index,
        public array $data,
        public int $size,
        public int $maxSize,
    ) {}

    public function addToIndex(int $logIndex, int $filePosition, int $timestamp, string $severity): void
    {
        if (! isset($this->data[$timestamp])) {
            $this->data[$timestamp] = [];
        }

        if (! isset($this->data[$timestamp][$severity])) {
            $this->data[$timestamp][$severity] = [];
        }

        $this->data[$timestamp][$severity][$logIndex] = $filePosition;
        $this->size++;
    }

    public function isFull(): bool
    {
        return $this->size >= $this->maxSize;
    }
}
