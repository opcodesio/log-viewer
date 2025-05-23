<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Collection;

class LogFolderCollection extends Collection
{
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
        $this->items = $this->sortBy->earliestTimestamp()->values()->toArray();

        return $this;
    }

    public function sortByLatestFirst(): self
    {
        $this->items = $this->sortByDesc->latestTimestamp()->values()->toArray();

        return $this;
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
        $this->items = collect($this->items)
            ->sort(function (LogFolder $a, LogFolder $b) {
                if ($a->isRoot() && ! $b->isRoot()) {
                    return -1;
                }
                if (! $a->isRoot() && $b->isRoot()) {
                    return 1;
                }

                return strcmp($a->cleanPath(), $b->cleanPath());
            })
            ->values()
            ->toArray();

        return $this;
    }

    public function sortAlphabeticallyDesc(): self
    {
        $this->items = collect($this->items)
            ->sort(function (LogFolder $a, LogFolder $b) {
                if ($a->isRoot() && ! $b->isRoot()) {
                    return -1;
                }
                if (! $a->isRoot() && $b->isRoot()) {
                    return 1;
                }

                return strcmp($b->cleanPath(), $a->cleanPath());
            })
            ->values()
            ->toArray();

        return $this;
    }
}
