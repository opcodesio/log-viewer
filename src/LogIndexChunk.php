<?php

namespace Opcodes\LogViewer;

class LogIndexChunk
{
    protected int $earliestTimestamp;
    protected int $latestTimestamp;

    public function __construct(
        public array $data,
        public int $index,
        public int $size,
    ) {}

    public static function fromDefinitionArray(array $definition): LogIndexChunk
    {
        $chunk = new self([], $definition['index'] ?? 0, $definition['size'] ?? 0);

        if (isset($definition['earliest_timestamp'])) {
            $chunk->earliestTimestamp = $definition['earliest_timestamp'];
        }

        if (isset($definition['latest_timestamp'])) {
            $chunk->latestTimestamp = $definition['latest_timestamp'];
        }

        return $chunk;
    }

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
        $this->earliestTimestamp = min($this->earliestTimestamp ?? $timestamp, $timestamp);
        $this->latestTimestamp = max($this->latestTimestamp ?? $timestamp, $timestamp);
    }

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'size' => $this->size,
            'earliest_timestamp' => $this->earliestTimestamp ?? null,
            'latest_timestamp' => $this->latestTimestamp ?? null,
        ];
    }
}
