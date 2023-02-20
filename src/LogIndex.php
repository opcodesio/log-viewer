<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Opcodes\LogViewer\Utils\Utils;

class LogIndex
{
    use Concerns\LogIndex\HasMetadata;
    use Concerns\LogIndex\CanCacheIndex;
    use Concerns\LogIndex\CanFilterIndex;
    use Concerns\LogIndex\CanIterateIndex;
    use Concerns\LogIndex\CanSplitIndexIntoChunks;
    use Concerns\LogIndex\PreservesIndexingProgress;

    const DEFAULT_CHUNK_SIZE = 20_000;

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

    public function addToIndex(int $filePosition, int|CarbonInterface $timestamp, string $severity, int $index = null): int
    {
        $logIndex = $index ?? $this->nextLogIndexToCreate ?? 0;

        if ($timestamp instanceof CarbonInterface) {
            $timestamp = $timestamp->timestamp;
        }

        $this->getCurrentChunk()->addToIndex($logIndex, $filePosition, $timestamp, $severity);

        $this->nextLogIndexToCreate = $logIndex + 1;

        if ($this->getCurrentChunk()->size >= $this->getMaxChunkSize()) {
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
        if (isset($this->currentChunk)) {
            $this->saveChunkToCache($this->currentChunk);
        }

        $this->saveMetadata();
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

    /**
     * @deprecated Will be removed in v2.0. Please use LogIndex::count()
     */
    public function total(): int
    {
        return $this->count();
    }

    public function count(): int
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
}
