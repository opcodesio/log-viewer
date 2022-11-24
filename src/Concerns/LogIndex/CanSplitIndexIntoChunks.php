<?php

namespace Opcodes\LogViewer\Concerns\LogIndex;

use Opcodes\LogViewer\Exceptions\InvalidChunkSizeException;
use Opcodes\LogViewer\LogIndexChunk;

trait CanSplitIndexIntoChunks
{
    protected int $maxChunkSize;

    protected array $currentChunkDefinition;

    protected LogIndexChunk $currentChunk;

    protected array $chunkDefinitions = [];

    /**
     * @throws InvalidChunkSizeException
     */
    public function setMaxChunkSize(int $size): void
    {
        if ($size < 1) {
            throw new InvalidChunkSizeException($size.' is not a valid chunk size. Must be higher than zero.');
        }

        $this->maxChunkSize = $size;
    }

    public function getMaxChunkSize(): int
    {
        return $this->maxChunkSize;
    }

    public function getCurrentChunk(): LogIndexChunk
    {
        if (! isset($this->currentChunk)) {
            $this->currentChunk = LogIndexChunk::fromDefinitionArray($this->currentChunkDefinition);

            if ($this->currentChunk->size > 0) {
                $this->currentChunk->data = $this->getChunkDataFromCache($this->currentChunk->index, []);
            }
        }

        return $this->currentChunk;
    }

    public function getChunkDefinitions(): array
    {
        return [
            ...$this->chunkDefinitions,
            $this->getCurrentChunk()->toArray(),
        ];
    }

    public function getChunkDefinition(int $index): ?array
    {
        return $this->getChunkDefinitions()[$index] ?? null;
    }

    public function getChunkCount(): int
    {
        return count($this->getChunkDefinitions());
    }

    public function getChunkData(int $index): ?array
    {
        $currentChunk = $this->getCurrentChunk();

        if ($index === $currentChunk?->index) {
            $chunkData = $currentChunk->data ?? [];
        } else {
            $chunkData = $this->getChunkDataFromCache($index);
        }

        return $chunkData;
    }

    protected function rotateCurrentChunk(): void
    {
        $this->saveChunkToCache($this->currentChunk);

        $this->chunkDefinitions[] = $this->currentChunk->toArray();

        $this->currentChunk = new LogIndexChunk([], $this->currentChunk->index + 1, 0);

        $this->saveMetadata();
    }

    protected function getRelevantItemsInChunk(array $chunkDefinition): int
    {
        $relevantItemsInChunk = 0;

        foreach ($chunkDefinition['level_counts'] as $level => $count) {
            if (! isset($this->filterLevels) || in_array($level, $this->filterLevels)) {
                $relevantItemsInChunk += $count;
            }
        }

        return $relevantItemsInChunk;
    }
}
