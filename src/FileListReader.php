<?php

namespace Arukompas\BetterLogViewer;

use Illuminate\Support\Collection;

class FileListReader
{
    public static ?Collection $_files = null;

    public static function findByName(string $fileName): ?LogFile
    {
        return (new self)->findFile($fileName);
    }

    public static function clearCache(): void
    {
        self::$_files = null;
    }

    /**
     * @return Collection|LogFile[]
     */
    public function getFiles()
    {
        if (! isset(self::$_files)) {
            $files = [];

            foreach (config('better-log-viewer.include_files', []) as $pattern) {
                $files = array_merge($files, glob(storage_path().'/logs/'.$pattern));
            }

            foreach (config('better-log-viewer.exclude_files', []) as $pattern) {
                $files = array_diff($files, glob(storage_path().'/logs/'.$pattern));
            }

            $files = array_reverse($files);
            $files = array_filter($files, 'is_file');

            static::$_files = collect($files ?? [])
                ->unique()
                ->map(fn ($file) => LogFile::fromPath($file))
                ->sortByDesc('name')
                ->values();
        }

        return static::$_files;
    }

    public function findFile($name): ?LogFile
    {
        return $this->getFiles()
            ->where('name', $name)
            ->first();
    }
}
