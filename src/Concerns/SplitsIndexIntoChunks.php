<?php

namespace Opcodes\LogViewer\Concerns;

use Illuminate\Support\Facades\Cache;
use Opcodes\LogViewer\Exceptions\InvalidChunkSizeException;
use Opcodes\LogViewer\LogIndexChunk;

trait SplitsIndexIntoChunks
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
                $this->currentChunk->data = Cache::get($this->chunkCacheKey($this->currentChunk->index), []);
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

    public function chunkCacheKey(int $index): string
    {
        return $this->cacheKey().':'.$index;
    }

    public function getChunkData(int $index): ?array
    {
        $currentChunk = $this->getCurrentChunk();

        if ($index === $currentChunk?->index) {
            $chunkData = $currentChunk->data ?? [];
        } else {
            $chunkData = Cache::get($this->chunkCacheKey($index));
        }

        return $chunkData;
    }

    protected function clearChunksFromCache(): void
    {
        foreach ($this->getChunkDefinitions() as $chunkDefinition) {
            Cache::forget($this->chunkCacheKey($chunkDefinition['index']));
        }
    }
}
