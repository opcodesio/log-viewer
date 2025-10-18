<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Collection;
use Opcodes\LogViewer\Enums\SortingMethod;
use Opcodes\LogViewer\Enums\SortingOrder;

class LogFolderCollection extends Collection
{
    public function sortUsing(string $method, string $order): self
    {
        if ($method === SortingMethod::ModifiedTime) {
            if ($order === SortingOrder::Ascending) {
                $this->items = $this->sortBy->earliestTimestamp()->values()->all();
            } else {
                $this->items = $this->sortByDesc->latestTimestamp()->values()->all();
            }
        } else {
            $this->items = collect($this->items)
                ->sort(function (LogFolder $a, LogFolder $b) use ($order) {
                    if ($a->isRoot() && ! $b->isRoot()) {
                        return -1;
                    }
                    if (! $a->isRoot() && $b->isRoot()) {
                        return 1;
                    }

                    return $order === SortingOrder::Ascending
                        ? strcmp($a->cleanPath(), $b->cleanPath())
                        : strcmp($b->cleanPath(), $a->cleanPath());
                })
                ->values()
                ->all();
        }

        return $this;
    }

    public static function fromFiles($files = []): LogFolderCollection
    {
        return new LogFolderCollection(
            (new LogFileCollection($files))
                ->groupBy(fn (LogFile $file) => $file->subFolder)
                ->map(fn ($files, $subFolder) => new LogFolder($subFolder, $files))
                ->values()
        );
    }

    public function sortByEarliestFirst(): self
    {
        return $this->sortUsing(SortingMethod::ModifiedTime, SortingOrder::Ascending);
    }

    public function sortByLatestFirst(): self
    {
        return $this->sortUsing(SortingMethod::ModifiedTime, SortingOrder::Descending);
    }

    public function sortByEarliestFirstIncludingFiles(): self
    {
        $this->sortByEarliestFirst();
        $this->each(fn (LogFolder $folder) => $folder->files()->sortByEarliestFirst());

        return $this;
    }

    public function sortByLatestFirstIncludingFiles(): self
    {
        $this->sortByLatestFirst();
        $this->each(fn (LogFolder $folder) => $folder->files()->sortByLatestFirst());

        return $this;
    }

    public function sortAlphabeticallyAsc(): self
    {
        return $this->sortUsing(SortingMethod::Alphabetical, SortingOrder::Ascending);
    }

    public function sortAlphabeticallyDesc(): self
    {
        return $this->sortUsing(SortingMethod::Alphabetical, SortingOrder::Descending);
    }
}
