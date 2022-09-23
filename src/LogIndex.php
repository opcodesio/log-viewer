<?php

namespace Opcodes\LogViewer;

use Carbon\Carbon;
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
    protected array $index;

    protected int $nextLogIndex;

    protected int $lastScannedFilePosition;

    protected ?int $filterFrom = null;

    protected ?int $filterTo = null;

    protected ?array $filterLevels = null;

    public function __construct(
        protected LogFile $file,
        protected ?string $query = null
    ) {
    }

    public function cacheKey(): string
    {
        return $this->file->cacheKey().':'.md5($this->query).':log-index';
    }

    public function metaCacheKey(): string
    {
        return $this->file->cacheKey().':'.md5($this->query).':metadata';
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

        if (! isset($this->index[$timestamp])) {
            $this->index[$timestamp] = [];
        }

        if (! isset($this->index[$timestamp][$severity])) {
            $this->index[$timestamp][$severity] = [];
        }

        $this->index[$timestamp][$severity][$nextLogIndex] = $filePosition;

        $this->nextLogIndex = $nextLogIndex + 1;

        return $nextLogIndex;
    }

    public function get(): array
    {
        if (! isset($this->index)) {
            $this->index = Cache::get($this->cacheKey(), []);
        }

        if (! $this->hasFilters()) {
            return $this->index;
        }

        $results = [];

        foreach ($this->index as $timestamp => $tsIndex) {
            if (isset($this->filterFrom) && $timestamp < $this->filterFrom) {
                continue;
            }
            if (isset($this->filterTo) && $timestamp > $this->filterTo) {
                continue;
            }

            if (! isset($this->filterLevels)) {
                $results[$timestamp] = $tsIndex;
            } else {
                $results[$timestamp] = [];

                foreach ($tsIndex as $level => $levelIndex) {
                    if (! in_array($level, $this->filterLevels)) {
                        continue;
                    }

                    $results[$timestamp][$level] = $levelIndex;
                }

                if (empty($results[$timestamp])) {
                    unset($results[$timestamp]);
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
        if (! isset($this->index)) {
            return;
        }

        Cache::put($this->cacheKey(), $this->index, $this->cacheTtl());
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

    protected function saveMetadata(): void
    {
        Cache::put(
            $this->metaCacheKey(),
            [
                'last_scanned_file_position' => $this->lastScannedFilePosition,
            ],
            $this->cacheTtl()
        );
    }

    protected function loadMetadata(): void
    {
        $data = Cache::get($this->metaCacheKey(), []);

        $this->lastScannedFilePosition = $data['last_scanned_file_position'] ?? 0;
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
