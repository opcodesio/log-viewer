<?php

namespace Opcodes\LogViewer\Concerns\LogIndex;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

trait PreservesIndexingProgress
{
    public function setLastScannedFilePosition(int $position): void
    {
        $this->lastScannedFilePosition = $position;
    }

    public function getLastScannedFilePosition(): int
    {
        if (! isset($this->lastScannedFilePosition)) {
            $this->loadMetadata();
        }

        return $this->lastScannedFilePosition;
    }

    public function setLastScannedIndex(int $index): void
    {
        $this->lastScannedIndex = $index;
    }

    public function getLastScannedIndex(): int
    {
        if (! isset($this->lastScannedIndex)) {
            $this->loadMetadata();
        }

        return $this->lastScannedIndex;
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

    public function getEarliestDate(): CarbonInterface
    {
        return CarbonImmutable::createFromTimestamp($this->getEarliestTimestamp());
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

    public function getLatestDate(): CarbonInterface
    {
        return CarbonImmutable::createFromTimestamp($this->getLatestTimestamp());
    }
}
