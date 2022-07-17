<?php

namespace Arukompas\BetterLogViewer;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileListReader
{
    /**
     * @return Collection|LogFile[]
     */
    public function getFiles()
    {
        $files = [];

        foreach (config('better-log-viewer.include_files', []) as $pattern) {
            $files = array_merge($files, glob(storage_path() . '/logs/' . $pattern));
        }

        foreach (config('better-log-viewer.exclude_files', []) as $pattern) {
            $files = array_diff($files, glob(storage_path() . '/logs/' . $pattern));
        }

        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');

        return collect($files ?? [])
            ->unique()
            ->map(fn ($file) => LogFile::fromPath($file))
            ->sortByDesc('name')
            ->values();
    }

    public function findFile($name)
    {
        return $this->getFiles()
            ->where('name', $name)
            ->first();
    }
}
