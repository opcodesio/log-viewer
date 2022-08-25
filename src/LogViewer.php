<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Collection;

class LogViewer
{
    public static ?Collection $_cachedFiles = null;

    /**
     * @return Collection|LogFile[]
     */
    public function getFiles()
    {
        if (! isset(self::$_cachedFiles)) {
            $files = [];

            foreach (config('log-viewer.include_files', []) as $pattern) {
                $files = array_merge($files, glob(storage_path().'/logs/'.$pattern));
            }

            foreach (config('log-viewer.exclude_files', []) as $pattern) {
                $files = array_diff($files, glob(storage_path().'/logs/'.$pattern));
            }

            $files = array_reverse($files);
            $files = array_filter($files, 'is_file');

            static::$_cachedFiles = collect($files ?? [])
                ->unique()
                ->map(fn ($file) => LogFile::fromPath($file))
                ->sortByDesc('name')
                ->values();
        }

        return static::$_cachedFiles;
    }

    public function getFile(?string $fileName): ?LogFile
    {
        if (empty($fileName)) {
            return null;
        }

        return $this->getFiles()
            ->where('name', $fileName)
            ->first();
    }

    public static function clearFileCache(): void
    {
        self::$_cachedFiles = null;
    }

    public function getRoutePrefix(): string
    {
        return config('log-viewer.route_path', 'log-viewer');
    }

    public function getRouteMiddleware(): array
    {
        return config('log-viewer.middleware', []) ?: ['web'];
    }
}
