<?php

namespace Opcodes\LogViewer;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Opcodes\LogViewer\Exceptions\InvalidChunkSizeException;

/**
 * The idea of this class is to keep a grip on the index of the log file
 * in a sustainable way, such as:
 *   - not having to load the full index into memory (chunking)
 *   - knowing when we should re-build the index
 *   - knowing about the different chunks and which ones are relevant to our search
 */
class LogIndex
{
    const DEFAULT_CHUNK_SIZE = 10_000;

    protected int $maxChunkSize;

    protected array $chunkDefinitions = [];

    protected LogIndexChunk $currentChunk;

    protected int $nextLogIndex;

    protected int $lastScannedFilePosition;

    protected ?int $filterFrom = null;

    protected ?int $filterTo = null;

    protected ?array $filterLevels = null;

    protected ?int $limit = null;

    public function __construct(
        protected LogFile $file,
        protected ?string $query = null
    ) {
        $this->loadMetadata();
    }

    public function getFile(): LogFile
    {
        return $this->file;
    }

    public function cacheKey(): string
    {
        return $this->file->cacheKey().':'.md5($this->query ?? '').':log-index';
    }

    public function metaCacheKey(): string
    {
        return $this->file->cacheKey().':'.md5($this->query ?? '').':metadata';
    }

    public function chunkCacheKey(int $index): string
    {
        return $this->cacheKey().':'.$index;
    }

    public function cacheTtl(): Carbon
    {
        return now()->addWeek();
    }

    public function reset(): void
    {
        foreach ($this->getChunkDefinitions() as $chunkDefinition) {
            Cache::forget($this->chunkCacheKey($chunkDefinition['index']));
        }

        Cache::forget($this->metaCacheKey());
        Cache::forget($this->cacheKey());

        // this will reset all properties to default, because it won't find any cached settings for this index
        $this->loadMetadata();
    }

    public function addToIndex(int $filePosition, int|Carbon $timestamp, string $severity): int
    {
        $nextLogIndex = $this->nextLogIndex ?? 0;

        if ($timestamp instanceof Carbon) {
            $timestamp = $timestamp->timestamp;
        }

        $this->currentChunk->addToIndex($nextLogIndex, $filePosition, $timestamp, $severity);

        $this->nextLogIndex = $nextLogIndex + 1;

        if ($this->currentChunk->size >= $this->getMaxChunkSize()) {
            $this->rotateCurrentChunk();
        }

        return $nextLogIndex;
    }

    public function getCurrentChunkSize(): int
    {
        return $this->currentChunk->size;
    }

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

    public function getChunkDefinitions(): array
    {
        return [
            ...$this->chunkDefinitions,
            $this->currentChunk->toArray(),
        ];
    }

    public function getChunkDefinition(int $index): ?array
    {
        return $this->getChunkDefinitions()[$index] ?? null;
    }

    public function getChunk(int $index): ?array
    {
        if (isset($this->currentChunk) && $index === $this->currentChunk->index) {
            return $this->currentChunk->data ?? [];
        }

        return Cache::get($this->chunkCacheKey($index));
    }

    public function getChunkCount(): int
    {
        return count($this->getChunkDefinitions());
    }

    protected function rotateCurrentChunk(): void
    {
        Cache::put(
            $this->chunkCacheKey($this->currentChunk->index),
            $this->currentChunk->data,
            $this->cacheTtl()
        );

        $this->chunkDefinitions[] = $this->currentChunk->toArray();

        $this->currentChunk = new LogIndexChunk([], $this->currentChunk->index + 1, 0);

        $this->saveMetadata();
    }

    public function get(int $limit = null): array
    {
        $results = [];
        $itemsAdded = 0;
        $limit = $limit ?? $this->limit;

        // TODO: sometimes we want the index in reverse, latest to oldest, highest to lowest, etc.

        // TODO: we can also instead limit it by reading the full chunk, sorting it, and then returning X items only.

        foreach ($this->getChunkDefinitions() as $chunkDefinition) {
            $chunk = $this->getChunk($chunkDefinition['index']);

            if (is_null($chunk)) {
                continue;
            }

            foreach ($chunk as $timestamp => $tsIndex) {
                if (isset($this->filterFrom) && $timestamp < $this->filterFrom) {
                    continue;
                }
                if (isset($this->filterTo) && $timestamp > $this->filterTo) {
                    continue;
                }

                // Timestamp is valid, let's start adding
                if (! isset($results[$timestamp])) {
                    $results[$timestamp] = [];
                }

                foreach ($tsIndex as $level => $levelIndex) {
                    if (isset($this->filterLevels) && ! in_array($level, $this->filterLevels)) {
                        continue;
                    }

                    // severity is valid, let's start adding
                    if (! isset($results[$timestamp][$level])) {
                        $results[$timestamp][$level] = [];
                    }

                    foreach ($levelIndex as $idx => $position) {
                        $results[$timestamp][$level][$idx] = $position;

                        if (isset($limit) && ++$itemsAdded >= $limit) {
                            break 4;
                        }
                    }
                }

                if (empty($results[$timestamp])) {
                    unset($results[$timestamp]);
                }
            }
        }

        return $results;
    }

    public function getFlatArray(int $limit = null): array
    {
        $results = [];
        $itemsAdded = 0;
        $limit = $limit ?? $this->limit;

        // TODO: sometimes we want the index in reverse, latest to oldest, highest to lowest, etc.

        // TODO: we can also instead limit it by reading the full chunk, sorting it, and then returning X items only.

        foreach ($this->getChunkDefinitions() as $chunkDefinition) {
            $chunk = $this->getChunk($chunkDefinition['index']);

            if (is_null($chunk)) {
                continue;
            }

            foreach ($chunk as $timestamp => $tsIndex) {
                if (isset($this->filterFrom) && $timestamp < $this->filterFrom) {
                    continue;
                }
                if (isset($this->filterTo) && $timestamp > $this->filterTo) {
                    continue;
                }

                foreach ($tsIndex as $level => $levelIndex) {
                    if (isset($this->filterLevels) && ! in_array($level, $this->filterLevels)) {
                        continue;
                    }

                    foreach ($levelIndex as $idx => $filePosition) {
                        $results[$idx] = $filePosition;

                        if (isset($limit) && ++$itemsAdded >= $limit) {
                            break 4;
                        }
                    }
                }
            }
        }

        ksort($results);

        return $results;
    }

    public function forDateRange(int|Carbon $from = null, int|Carbon $to = null): self
    {
        if ($from instanceof Carbon) {
            $from = $from->timestamp;
        }

        if ($to instanceof Carbon) {
            $to = $to->timestamp;
        }

        $this->filterFrom = $from;
        $this->filterTo = $to;

        return $this;
    }

    public function forLevels(string|array $levels = null): self
    {
        if (is_string($levels)) {
            $levels = [$levels];
        }

        if (is_array($levels)) {
            $this->filterLevels = array_map('strtolower', $levels);
        } else {
            $this->filterLevels = null;
        }

        return $this;
    }

    public function limit(int $limit = null): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function save(): void
    {
        if (! isset($this->currentChunk)) {
            return;
        }

        Cache::put(
            $this->chunkCacheKey($this->currentChunk->index),
            $this->currentChunk->data,
            $this->cacheTtl()
        );

        $this->saveMetadata();
    }

    public function setLastScannedFilePosition(int $position): void
    {
        $this->lastScannedFilePosition = $position;

        $this->saveMetadata();
    }

    public function getLastScannedFilePosition(): int
    {
        if (! isset($this->lastScannedFilePosition)) {
            $this->loadMetadata();
        }

        return $this->lastScannedFilePosition;
    }

    public function incomplete(): bool
    {
        return $this->file->size() !== $this->getLastScannedFilePosition();
    }

    public function getEarliestTimestamp(): ?int
    {
        $earliestTimestamp = null;

        if ($this->hasFilters()) {
            // because it has filters, we can no longer use our chunk definitions, which has
            // values for the whole index and not just particular levels/dates.
            foreach ($this->get() as $timestamp => $tsIndex) {
                $earliestTimestamp = min($timestamp, $earliestTimestamp ?? $timestamp);
            }
        } else {
            foreach ($this->getChunkDefinitions() as $chunkDefinition) {
                if (! isset($chunkDefinition['earliest_timestamp'])) {
                    continue;
                }

                $earliestTimestamp = min(
                    $chunkDefinition['earliest_timestamp'],
                    $earliestTimestamp ?? $chunkDefinition['earliest_timestamp']
                );
            }
        }

        return $earliestTimestamp;
    }

    public function getEarliestDate(): Carbon
    {
        return Carbon::createFromTimestamp($this->getEarliestTimestamp());
    }

    public function getLatestTimestamp(): ?int
    {
        $latestTimestamp = null;

        if ($this->hasFilters()) {
            // because it has filters, we can no longer use our chunk definitions, which has
            // values for the whole index and not just particular levels/dates.
            foreach ($this->get() as $timestamp => $tsIndex) {
                $latestTimestamp = max($timestamp, $latestTimestamp ?? $timestamp);
            }
        } else {
            foreach ($this->getChunkDefinitions() as $chunkDefinition) {
                if (! isset($chunkDefinition['latest_timestamp'])) {
                    continue;
                }

                $latestTimestamp = max(
                    $chunkDefinition['latest_timestamp'],
                    $latestTimestamp ?? $chunkDefinition['latest_timestamp']
                );
            }
        }

        return $latestTimestamp;
    }

    public function getLatestDate(): Carbon
    {
        return Carbon::createFromTimestamp($this->getLatestTimestamp());
    }

    public function getLevelCounts(): Collection
    {
        $counts = collect(Level::caseValues())->mapWithKeys(fn ($case) => [$case => 0]);

        if (! $this->hasDateFilters()) {
            // without date filters, we can use a faster approach
            foreach ($this->getChunkDefinitions() as $chunkDefinition) {
                foreach ($chunkDefinition['level_counts'] as $severity => $count) {
                    $counts[$severity] += $count;
                }
            }
        } else {
            foreach ($this->get() as $timestamp => $tsIndex) {
                foreach ($tsIndex as $severity => $logIndex) {
                    $counts[$severity] += count($logIndex);
                }
            }
        }

        return $counts;
    }

    protected function saveMetadata(): void
    {
        Cache::put(
            $this->metaCacheKey(),
            [
                'last_scanned_file_position' => $this->lastScannedFilePosition,
                'next_log_index' => $this->nextLogIndex,
                'max_chunk_size' => $this->maxChunkSize,
                'current_chunk_index' => $this->currentChunk->index,
                'chunk_definitions' => $this->chunkDefinitions,
                'current_chunk_definition' => $this->currentChunk->toArray(),
            ],
            $this->cacheTtl()
        );
    }

    protected function loadMetadata(): void
    {
        $data = Cache::get($this->metaCacheKey(), []);

        $this->lastScannedFilePosition = $data['last_scanned_file_position'] ?? 0;
        $this->nextLogIndex = $data['next_log_index'] ?? 0;
        $this->maxChunkSize = $data['max_chunk_size'] ?? self::DEFAULT_CHUNK_SIZE;
        $this->chunkDefinitions = $data['chunk_definitions'] ?? [];

        $this->currentChunk = LogIndexChunk::fromDefinitionArray($data['current_chunk_definition'] ?? []);
        $this->currentChunk->data = Cache::get($this->chunkCacheKey($this->currentChunk->index), []);
    }

    protected function hasDateFilters(): bool
    {
        return isset($this->filterFrom)
            || isset($this->filterTo);
    }

    protected function hasFilters(): bool
    {
        return $this->hasDateFilters()
            || isset($this->filterLevels);
    }
}
