<?php

namespace Opcodes\LogViewer;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The idea of this class is to keep a grip on the index of the log file
 * in a sustainable way, such as:
 *   - not having to load the full index into memory (chunking)
 *   - knowing when we should re-build the index
 *   - knowing about the different chunks and which ones are relevant to our search
 */
class LogIndex
{
    use Concerns\CanFilterIndex;
    use Concerns\CanIterateIndex;
    use Concerns\SplitsIndexIntoChunks;

    const DIRECTION_FORWARD = 'forward';

    const DIRECTION_BACKWARD = 'backward';

    const DEFAULT_CHUNK_SIZE = 10_000;

    protected int $nextLogIndexToCreate;

    protected int $lastScannedFilePosition;

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

    public function cacheTtl(): Carbon
    {
        return now()->addWeek();
    }

    public function clearCache(): void
    {
        $this->clearChunksFromCache();

        Cache::forget($this->metaCacheKey());
        Cache::forget($this->cacheKey());

        // this will reset all properties to default, because it won't find any cached settings for this index
        $this->loadMetadata();
    }

    public function addToIndex(int $filePosition, int|Carbon $timestamp, string $severity): int
    {
        $logIndex = $this->nextLogIndexToCreate ?? 0;

        if ($timestamp instanceof Carbon) {
            $timestamp = $timestamp->timestamp;
        }

        $this->currentChunk->addToIndex($logIndex, $filePosition, $timestamp, $severity);

        $this->nextLogIndexToCreate = $logIndex + 1;

        if ($this->currentChunk->size >= $this->getMaxChunkSize()) {
            $this->rotateCurrentChunk();
        }

        return $logIndex;
    }

    public function getPositionForIndex(int $indexToFind): ?int
    {
        $itemsSkipped = 0;

        foreach ($this->getChunkDefinitions() as $chunkDefinition) {
            if (($itemsSkipped + $chunkDefinition['size']) <= $indexToFind) {
                // not in this index, let's move on
                $itemsSkipped += $chunkDefinition['size'];

                continue;
            }

            foreach ($this->getChunkData($chunkDefinition['index']) as $timestamp => $tsIndex) {
                foreach ($tsIndex as $level => $levelIndex) {
                    foreach ($levelIndex as $index => $position) {
                        if ($index === $indexToFind) {
                            return $position;
                        }
                    }
                }
            }
        }

        return null;
    }

    public function get(int $limit = null): array
    {
        $results = [];
        $itemsAdded = 0;
        $limit = $limit ?? $this->limit;
        $skip = $this->skip;
        $chunkDefinitions = $this->getChunkDefinitions();

        $this->sortKeys($chunkDefinitions);

        foreach ($chunkDefinitions as $chunkDefinition) {
            if (isset($skip)) {
                $relevantItemsInChunk = $this->getRelevantItemsInChunk($chunkDefinition);

                if ($relevantItemsInChunk <= $skip) {
                    $skip -= $relevantItemsInChunk;

                    continue;
                }
            }

            $chunk = $this->getChunkData($chunkDefinition['index']);

            if (empty($chunk)) {
                continue;
            }

            $this->sortKeys($chunk);

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

                    $this->sortKeys($levelIndex);

                    foreach ($levelIndex as $idx => $position) {
                        if ($skip > 0) {
                            $skip--;

                            continue;
                        }

                        $results[$timestamp][$level][$idx] = $position;

                        if (isset($limit) && ++$itemsAdded >= $limit) {
                            break 4;
                        }
                    }

                    if (empty($results[$timestamp][$level])) {
                        unset($results[$timestamp][$level]);
                    }
                }

                if (empty($results[$timestamp])) {
                    unset($results[$timestamp]);
                }
            }
        }

        return $results;
    }

    public function getFlatIndex(int $limit = null): array
    {
        $results = [];
        $itemsAdded = 0;
        $limit = $limit ?? $this->limit;
        $skip = $this->skip;
        $chunkDefinitions = $this->getChunkDefinitions();

        $this->sortKeys($chunkDefinitions);

        foreach ($chunkDefinitions as $chunkDefinition) {
            if (isset($skip)) {
                $relevantItemsInChunk = $this->getRelevantItemsInChunk($chunkDefinition);

                if ($relevantItemsInChunk <= $skip) {
                    $skip -= $relevantItemsInChunk;

                    continue;
                }
            }

            $chunk = $this->getChunkData($chunkDefinition['index']);

            if (is_null($chunk)) {
                continue;
            }

            $this->sortKeys($chunk);

            foreach ($chunk as $timestamp => $tsIndex) {
                if (isset($this->filterFrom) && $timestamp < $this->filterFrom) {
                    continue;
                }
                if (isset($this->filterTo) && $timestamp > $this->filterTo) {
                    continue;
                }

                $itemsWithinThisTimestamp = [];

                foreach ($tsIndex as $level => $levelIndex) {
                    if (isset($this->filterLevels) && ! in_array($level, $this->filterLevels)) {
                        continue;
                    }

                    foreach ($levelIndex as $idx => $filePosition) {
                        $itemsWithinThisTimestamp[$idx] = $filePosition;
                    }
                }

                $this->sortKeys($itemsWithinThisTimestamp);

                foreach ($itemsWithinThisTimestamp as $idx => $filePosition) {
                    if ($skip > 0) {
                        $skip--;

                        continue;
                    }

                    $results[$idx] = $filePosition;

                    if (isset($limit) && ++$itemsAdded >= $limit) {
                        break 3;
                    }
                }
            }
        }

        return $results;
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

    public function total(): int
    {
        return array_reduce($this->getChunkDefinitions(), function ($sum, $chunkDefinition) {
            foreach ($chunkDefinition['level_counts'] as $level => $count) {
                if (! isset($this->filterLevels) || in_array($level, $this->filterLevels)) {
                    $sum += $count;
                }
            }

            return $sum;
        }, 0);
    }

    protected function sortKeys(array &$array): void
    {
        if ($this->isBackward()) {
            krsort($array);
        } else {
            ksort($array);
        }
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

    protected function saveMetadata(): void
    {
        Cache::put(
            $this->metaCacheKey(),
            [
                'last_scanned_file_position' => $this->lastScannedFilePosition,
                'next_log_index_to_create' => $this->nextLogIndexToCreate,
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
        $this->nextLogIndexToCreate = $data['next_log_index_to_create'] ?? 0;
        $this->maxChunkSize = $data['max_chunk_size'] ?? self::DEFAULT_CHUNK_SIZE;
        $this->chunkDefinitions = $data['chunk_definitions'] ?? [];

        $this->currentChunk = LogIndexChunk::fromDefinitionArray($data['current_chunk_definition'] ?? []);
        $this->currentChunk->data = Cache::get($this->chunkCacheKey($this->currentChunk->index), []);
    }
}
