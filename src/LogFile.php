<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Events\LogFileDeleted;

class LogFile
{
    public string $identifier;

    public string $subFolder = '';

    public function __construct(
        public string $name,
        public string $path,
    ) {
        $this->identifier = Str::substr(md5($path), -8, 8).'-'.$name;

        // by default, we load all logs from the storage/logs folder, so we can
        // safely disregard that part because it's always going to be the same.
        $folder = str_replace(Str::finish(storage_path('logs'), DIRECTORY_SEPARATOR), '', $path);

        // now we're left with something like `folderA/laravel.log`. Let's remove the file name because we already know it.
        $this->subFolder = str_replace($name, '', $folder);
    }

    public static function fromPath(string $filePath): LogFile
    {
        return new self(
            basename($filePath),
            $filePath,
        );
    }

    public function logs(): LogReader
    {
        return LogReader::instance($this);
    }

    public function size(): int
    {
        return filesize($this->path);
    }

    public function sizeFormatted(): string
    {
        return bytes_formatted($this->size());
    }

    public function downloadUrl(): string
    {
        return route('blv.download-file', $this->identifier);
    }

    public function download()
    {
        return response()->download($this->path);
    }

    protected function cacheKey(): string
    {
        return 'log-viewer:file:'.md5($this->path);
    }

    protected function relatedCacheKeysKey(): string
    {
        return $this->cacheKey().':related-cache-keys';
    }

    public function addRelatedCacheKey(string $key): void
    {
        $keys = $this->getRelatedCacheKeys();
        $keys[] = $key;
        Cache::put(
            $this->relatedCacheKeysKey(),
            array_unique($keys),
            // because all individual cache keys will expire far quicker than one week, it's OK for us to
            // expire this overarching key, because all dependent keys will have expired by that time.
            now()->addWeek()
        );
    }

    public function getRelatedCacheKeys(): array
    {
        return Cache::get($this->relatedCacheKeysKey(), []);
    }

    protected function indexCacheKeyForQuery(string $query = ''): string
    {
        return $this->cacheKey().':'.md5($query).':index';
    }

    public function saveIndexForQuery(array $indexData, string $query = ''): void
    {
        $key = $this->indexCacheKeyForQuery($query);

        Cache::put(
            $key,
            $indexData,
            now()->addDay(),
        );

        $this->addRelatedCacheKey($key);
    }

    public function getIndexForQuery(string $query = '', $default = []): array
    {
        return Cache::get($this->indexCacheKeyForQuery($query), $default);
    }

    public function clearCache(): void
    {
        foreach ($this->getRelatedCacheKeys() as $relatedCacheKey) {
            Cache::forget($relatedCacheKey);
        }

        Cache::forget($this->relatedCacheKeysKey());
    }

    public function delete(): void
    {
        $this->clearCache();
        unlink($this->path);
        LogFileDeleted::dispatch($this);
    }
}
