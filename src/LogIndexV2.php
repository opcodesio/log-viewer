<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Opcodes\LogViewer\Utils\Utils;

class LogIndexV2
{
    use Concerns\LogIndex\CanCacheIndex;
    use Concerns\LogIndex\CanFilterIndex;
    use Concerns\LogIndex\CanIterateIndex;

    // use Concerns\LogIndex\CanSplitIndexIntoChunks;
    use Concerns\LogIndex\PreservesIndexingProgress;

    const DEFAULT_CHUNK_SIZE = 5_000;

    private int $chunkSize = self::DEFAULT_CHUNK_SIZE;

    private array $index = [];

    private array $currentGroup = [];

    public string $identifier;

    protected int $nextLogIndexToCreate;

    protected int $lastScannedFilePosition;

    protected int $lastScannedIndex;

    public function __construct(
        public LogFile $file,
        protected ?string $query = null
    ) {
        $this->identifier = Utils::shortMd5($this->query ?? '');
        $this->loadMetadata();
    }

    public function setChunkSize(int $size): static
    {
        $this->chunkSize = $size;

        return $this;
    }

    public function setLastScannedFilePosition(int $position): void
    {
        $this->lastScannedFilePosition = $position;

        if (isset($this->currentGroup['pos_to'])) {
            $this->currentGroup['pos_to'] = max($this->currentGroup['pos_to'], $position);
        }
    }

    public function addToIndex(int $filePosition, int|CarbonInterface $timestamp, string $severity, int $index = null): int
    {
        if (($this->currentGroup['count'] ?? 0) >= $this->chunkSize) {
            $this->currentGroup['pos_to'] = max($filePosition, $this->currentGroup['pos_to'] ?? $filePosition);
            $this->index[] = $this->currentGroup;
            $this->currentGroup = [];
        }

        $logIndex = $index ?? $this->nextLogIndexToCreate ?? 0;
        $timestamp = $timestamp instanceof CarbonInterface ? $timestamp->timestamp : $timestamp;

        $this->currentGroup['idx_from'] = $this->currentGroup['idx_from'] ?? $logIndex;
        $this->currentGroup['idx_to'] = $logIndex;
        $this->currentGroup['pos_from'] = min($filePosition, $this->currentGroup['pos_from'] ?? $filePosition);
        $this->currentGroup['pos_to'] = max($filePosition, $this->currentGroup['pos_to'] ?? $filePosition);
        $this->currentGroup['ts_from'] = min($timestamp, $this->currentGroup['ts_from'] ?? $timestamp);
        $this->currentGroup['ts_to'] = max($timestamp, $this->currentGroup['ts_to'] ?? $timestamp);
        $this->currentGroup['levels'][$severity] = ($this->currentGroup['levels'][$severity] ?? 0) + 1;
        $this->currentGroup['count'] = ($this->currentGroup['count'] ?? 0) + 1;

        $this->nextLogIndexToCreate = $logIndex + 1;

        return $logIndex;
    }

    public function getMetadata(): array
    {
        return [
            'query' => $this->getQuery(),
            'identifier' => $this->identifier,
            'last_scanned_file_position' => $this->lastScannedFilePosition,
            'last_scanned_index' => $this->lastScannedIndex,
            'next_log_index_to_create' => $this->nextLogIndexToCreate,
            'index' => $this->getFullIndex(),
        ];
    }

    public function getFullIndex(): array
    {
        if (empty($this->currentGroup)) {
            return $this->index;
        }

        return array_merge($this->index, [$this->currentGroup]);
    }

    protected function saveMetadata(): void
    {
        $this->saveMetadataToCache();
    }

    protected function loadMetadata(): void
    {
        $data = $this->getMetadataFromCache();

        $this->lastScannedFilePosition = $data['last_scanned_file_position'] ?? 0;
        $this->lastScannedIndex = $data['last_scanned_index'] ?? 0;
        $this->nextLogIndexToCreate = $data['next_log_index_to_create'] ?? 0;
        $this->index = $data['index'] ?? [];
        $this->currentGroup = array_pop($this->index) ?? [];
    }

    public function get(int $index = null): array
    {
        if (isset($index)) {
            return $this->getFullIndex()[$index] ?? [];
        }

        return $this->getFullIndex();
    }

    public function nextGroup(): array
    {
        $groups = $this->getFullIndex();

        if ($this->isBackward()) {
            $groups = array_reverse($groups);
        }

        $skippedEntries = 0;

        $nextGroup = array_shift($groups) ?? [];
        $entriesFoundInGroup = $this->getEntriesCountFromGroup($nextGroup);

        while (count($groups) > 0 && ($this->skip >= $entriesFoundInGroup || $entriesFoundInGroup === 0)) {
            $this->skip -= $entriesFoundInGroup;
            $skippedEntries += $entriesFoundInGroup;
            $nextGroup = array_shift($groups);
            $entriesFoundInGroup = $this->getEntriesCountFromGroup($nextGroup);
        }

        if ($entriesFoundInGroup === 0 || $entriesFoundInGroup <= $this->skip) {
            return [];
        }

        return array_merge($nextGroup, [
            'skipped_entries' => $skippedEntries,
        ]);
    }

    public function getEntriesCountFromGroup(array $group): int
    {
        if (! $this->hasFilters()) {
            return $group['count'];
        }

        $count = 0;

        foreach (($group['levels'] ?? []) as $level => $levelCount) {
            if (empty($this->exceptLevels) || ! in_array($level, $this->exceptLevels)) {
                $count += $levelCount;
            }
        }

        // make sure to take into account date filters
        if (isset($this->filterFrom) && $group['ts_to'] < $this->filterFrom) {
            return 0;
        }

        if (isset($this->filterTo) && $group['ts_from'] > $this->filterTo) {
            return 0;
        }

        return $count;
    }

    public function save(): void
    {
        $this->saveMetadata();
    }

    public function getLevelCounts(): Collection
    {
        $levelCounts = [];
        foreach ($this->getFullIndex() as $group) {
            foreach ($group['levels'] as $level => $count) {
                $levelCounts[$level] = ($levelCounts[$level] ?? 0) + $count;
            }
        }

        return collect($levelCounts);
    }

    public function count(): int
    {
        $totalCount = 0;
        foreach ($this->getFullIndex() as $group) {
            $totalCount += $group['count'];
        }

        return $totalCount;
    }

    public function getEarliestTimestamp(): ?int
    {
        $earliestTimestamp = null;

        foreach ($this->get() as $group) {
            if (! isset($group['ts_from'])) {
                continue;
            }

            $earliestTimestamp = min(
                $group['ts_from'],
                $earliestTimestamp ?? $group['ts_from']
            );
        }

        return $earliestTimestamp;
    }

    public function getLatestTimestamp(): ?int
    {
        $latestTimestamp = null;

        foreach ($this->get() as $group) {
            if (! isset($group['ts_to'])) {
                continue;
            }

            $latestTimestamp = max(
                $group['ts_to'],
                $latestTimestamp ?? $group['ts_to']
            );
        }

        return $latestTimestamp;
    }
}
