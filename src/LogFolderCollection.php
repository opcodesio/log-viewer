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
}
