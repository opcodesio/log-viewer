<?php

namespace Opcodes\LogViewer\Concerns\LogIndex;

use Carbon\CarbonInterface;

trait CanFilterIndex
{
    protected ?int $filterFrom = null;
    protected ?int $filterTo = null;
    protected ?array $includeLevels = null;
    protected ?array $excludeLevels = null;
    protected ?int $limit = null;
    protected ?int $skip = null;

    public function setQuery(string $query = null): self
    {
        if ($this->query !== $query) {
            $this->query = $query;

            $this->loadMetadata();
        }

        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function forDateRange(int|CarbonInterface $from = null, int|CarbonInterface $to = null): self
    {
        if ($from instanceof CarbonInterface) {
            $from = $from->timestamp;
        }

        if ($to instanceof CarbonInterface) {
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
            $this->includeLevels = array_filter($levels);
        } else {
            $this->includeLevels = null;
        }

        return $this;
    }

    public function exceptLevels(string|array $levels = null): self
    {
        if (is_null($levels)) {
            $this->excludeLevels = null;
        } elseif (is_array($levels)) {
            $this->excludeLevels = $levels;
        } else {
            $this->excludeLevels = [$levels];
        }

        return $this;
    }

    public function forLevel(string $level = null): self
    {
        return $this->forLevels($level);
    }

    public function isLevelSelected(string $level): bool
    {
        return (is_null($this->includeLevels) || in_array($level, $this->includeLevels))
            && (is_null($this->excludeLevels) || ! in_array($level, $this->excludeLevels));
    }

    public function skip(int $skip = null): self
    {
        $this->skip = $skip;

        return $this;
    }

    public function getSkip(): ?int
    {
        return $this->skip;
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

    protected function hasDateFilters(): bool
    {
        return isset($this->filterFrom)
            || isset($this->filterTo);
    }

    protected function hasFilters(): bool
    {
        return $this->hasDateFilters()
            || isset($this->includeLevels)
            || isset($this->excludeLevels);
    }
}
