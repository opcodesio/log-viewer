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

    protected int $chunkSize;

    protected array $currentChunk;

    protected int $currentChunkSize = 0;

    protected int $chunkCount = 1;

    protected int $nextLogIndex;

    protected int $lastScannedFilePosition;

    protected ?int $filterFrom = null;

    protected ?int $filterTo = null;

    protected ?array $filterLevels = null;

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
        return $this->file->cacheKey().':'.md5($this->query).':log-index';
    }

    public function metaCacheKey(): string
    {
        return $this->file->cacheKey().':'.md5($this->query).':metadata';
    }

    public function chunkCacheKey(int $index): string
    {
        return $this->cacheKey().':'.$index;
    }

    public function cacheTtl(): Carbon
    {
        return now()->addWeek();
    }

    public function addToIndex(int $filePosition, int|Carbon $timestamp, string $severity): int
    {
        $nextLogIndex = $this->nextLogIndex ?? 0;

        if ($timestamp instanceof Carbon) {
            $timestamp = $timestamp->timestamp;
        }

        if (! isset($this->currentChunk[$timestamp])) {
            $this->currentChunk[$timestamp] = [];
        }

        if (! isset($this->currentChunk[$timestamp][$severity])) {
            $this->currentChunk[$timestamp][$severity] = [];
        }

        $this->currentChunk[$timestamp][$severity][$nextLogIndex] = $filePosition;

        $this->nextLogIndex = $nextLogIndex + 1;
        $this->currentChunkSize++;

        if ($this->currentChunkSize >= $this->getChunkSize()) {
            $this->rotateChunk();
        }

        return $nextLogIndex;
    }

    public function getCurrentChunkSize(): int
    {
        return $this->currentChunkSize;
    }

    /**
     * @throws InvalidChunkSizeException
     */
    public function setChunkSize(int $size): void
    {
        if ($size < 1) {
            throw new InvalidChunkSizeException($size.' is not a valid chunk size. Must be higher than zero.');
        }

        $this->chunkSize = $size;
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function getChunk(int $index): ?array
    {
        if ($index === ($this->getChunkCount() - 1)) {
            return $this->currentChunk ?? [];
        }

        return Cache::get($this->chunkCacheKey($index));
    }

    public function getChunkCount(): int
    {
        return $this->chunkCount ?? 1;
    }

    protected function rotateChunk(): void
    {
        $chunkIndex = $this->chunkCount - 1;
        Cache::put($this->chunkCacheKey($chunkIndex), $this->currentChunk, $this->cacheTtl());

        $this->currentChunk = [];
        $this->currentChunkSize = 0;
        $this->chunkCount++;

        $this->saveMetadata();
    }

    public function get(): array
    {
        $results = [];

        foreach (range(0, $this->getChunkCount() - 1) as $chunkIndex) {
            $chunk = $this->getChunk($chunkIndex);

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

                if (! isset($this->filterLevels) && ! isset($results[$timestamp])) {
                    $results[$timestamp] = $tsIndex;
                } else {
                    if (! isset($results[$timestamp])) {
                        $results[$timestamp] = [];
                    }

                    foreach ($tsIndex as $level => $levelIndex) {
                        if (isset($this->filterLevels) && ! in_array($level, $this->filterLevels)) {
                            continue;
                        }

                        if (! isset($results[$timestamp][$level])) {
                            $results[$timestamp][$level] = $levelIndex;
                        } else {
                            foreach ($levelIndex as $idx => $position) {
                                $results[$timestamp][$level][$idx] = $position;
                            }
                        }
                    }

                    if (empty($results[$timestamp])) {
                        unset($results[$timestamp]);
                    }
                }
            }
        }

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

    public function save(): void
    {
        if (! isset($this->currentChunk)) {
            return;
        }

        Cache::put($this->cacheKey(), $this->currentChunk, $this->cacheTtl());
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

        foreach ($this->get() as $timestamp => $tsIndex) {
            $earliestTimestamp = min($timestamp, $earliestTimestamp ?? $timestamp);
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

        foreach ($this->get() as $timestamp => $tsIndex) {
            $latestTimestamp = max($timestamp, $latestTimestamp ?? $timestamp);
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

        foreach ($this->get() as $timestamp => $tsIndex) {
            foreach ($tsIndex as $severity => $logIndex) {
                $counts[$severity] += count($logIndex);
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
                'chunk_size' => $this->chunkSize,
                'chunk_count' => $this->chunkCount,
            ],
            $this->cacheTtl()
        );
    }

    protected function loadMetadata(): void
    {
        $data = Cache::get($this->metaCacheKey(), []);

        $this->lastScannedFilePosition = $data['last_scanned_file_position'] ?? 0;
        $this->chunkSize = $data['chunk_size'] ?? self::DEFAULT_CHUNK_SIZE;
        $this->chunkCount = $data['chunk_count'] ?? 1;

        $this->loadCurrentChunk();
    }

    protected function loadCurrentChunk(): void
    {
        $latestChunkIndex = $this->chunkCount - 1;

        $this->currentChunk = Cache::get($this->chunkCacheKey($latestChunkIndex), []);

        $this->currentChunkSize = 0;

        foreach ($this->currentChunk as $ts => $tsIndex) {
            foreach ($tsIndex as $level => $levelIndex) {
                $this->currentChunkSize += count($levelIndex);
            }
        }
    }

    protected function hasFilters(): bool
    {
        return isset($this->filterFrom)
            || isset($this->filterTo)
            || isset($this->filterLevels);
    }

    public function __destruct()
    {
        $this->save();
    }
}
