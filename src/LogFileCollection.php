<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Collection;
use Opcodes\LogViewer\Enums\SortingMethod;
use Opcodes\LogViewer\Enums\SortingOrder;
use Opcodes\LogViewer\Readers\MultipleLogReader;

/**
 * @var LogFile[] $items
 */
class LogFileCollection extends Collection
{
    public function sortUsing(string $method, string $order): self
    {
        if ($method === SortingMethod::ModifiedTime) {
            if ($order === SortingOrder::Ascending) {
                $this->items = $this->sortBy(function (LogFile $file) {
                    return $file->earliestTimestamp().($file->name ?? '');
                }, SORT_NATURAL)->values()->all();
            } else {
                $this->items = $this->sortByDesc(function (LogFile $file) {
                    return $file->latestTimestamp().($file->name ?? '');
                }, SORT_NATURAL)->values()->all();
            }
        } else {
            if ($order === SortingOrder::Ascending) {
                $this->items = $this->sortBy('name')->values()->all();
            } else {
                $this->items = $this->sortByDesc('name')->values()->all();
            }
        }

        return $this;
    }

    public function sortByEarliestFirst(): self
    {
        return $this->sortUsing(SortingMethod::ModifiedTime, SortingOrder::Ascending);
    }

    public function sortByLatestFirst(): self
    {
        return $this->sortUsing(SortingMethod::ModifiedTime, SortingOrder::Descending);
    }

    public function sortAlphabeticallyAsc(): self
    {
        return $this->sortUsing(SortingMethod::Alphabetical, SortingOrder::Ascending);
    }

    public function sortAlphabeticallyDesc(): self
    {
        return $this->sortUsing(SortingMethod::Alphabetical, SortingOrder::Descending);
    }

    public function latest(): ?LogFile
    {
        return $this->sortByDesc->latestTimestamp()->first();
    }

    public function earliest(): ?LogFile
    {
        return $this->sortBy->earliestTimestamp()->first();
    }

    public function logs(): MultipleLogReader
    {
        return new MultipleLogReader($this->items);
    }
}
