<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Collection;

class LogFileCollection extends Collection
{
    public function sortByEarliestFirst(): self
    {
        $this->items = $this->sortBy->earliestTimestamp()->values()->toArray();

        return $this;
    }

    public function sortByLatestFirst(): self
    {
        $this->items = $this->sortByDesc->latestTimestamp()->values()->toArray();

        return $this;
    }

    public function latest(): ?LogFile
    {
        return $this->sortByDesc->latestTimestamp()->first();
    }

    public function earliest(): ?LogFile
    {
        return $this->sortBy->earliestTimestamp()->first();
    }
}
