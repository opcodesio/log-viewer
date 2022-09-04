<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Facades\LogViewer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogFile
{
    public string $identifier;

    public string $subFolder = '';

    protected array $metaData;

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

        $this->metaData = Cache::get($this->metaDataCacheKey(), []);
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

    public function download(): BinaryFileResponse
    {
        return response()->download($this->path);
    }

    protected function cacheTtl()
    {
        return now()->addWeek();
    }

    protected function cacheKey(): string
    {
        return 'log-viewer:'.LogViewer::version().':file:'.md5($this->path);
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
            $this->cacheTtl()
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

    public function saveIndexDataForQuery(array $indexData, string $query = ''): void
    {
        $key = $this->indexCacheKeyForQuery($query);
        Cache::put($key, $indexData, $this->cacheTtl());
        $this->addRelatedCacheKey($key);
    }

    public function getIndexDataForQuery(string $query = '', $default = []): array
    {
        return Cache::get($this->indexCacheKeyForQuery($query), $default);
    }

    public function saveLastScanFileSizeForQuery(int $lastScanFileSize, string $query = ''): void
    {
        $fileSizeKey = $this->indexCacheKeyForQuery($query).':filesize';
        Cache::put($fileSizeKey, $lastScanFileSize, $this->cacheTtl());
        $this->addRelatedCacheKey($fileSizeKey);
    }

    public function getLastScanFileSizeForQuery(string $query = '', $default = 0): int
    {
        return Cache::get($this->indexCacheKeyForQuery($query).':filesize', $default);
    }

    public function clearCache(): void
    {
        foreach ($this->getRelatedCacheKeys() as $relatedCacheKey) {
            Cache::forget($relatedCacheKey);
        }

        Cache::forget($this->metaDataCacheKey());
        Cache::forget($this->relatedCacheKeysKey());
    }

    protected function metaDataCacheKey(): string
    {
        return $this->cacheKey().':metadata';
    }

    public function setMetaData(string $attribute, $value): void
    {
        $this->metaData[$attribute] = $value;
    }

    public function getMetaData(string $attribute = null, $default = null): mixed
    {
        if (! isset($this->metaData)) {
            $this->metaData = Cache::get($this->metaDataCacheKey(), []);
        }

        if (isset($attribute)) {
            return $this->metaData[$attribute] ?? $default;
        }

        return $this->metaData;
    }

    public function saveMetaData(): void
    {
        Cache::put($this->metaDataCacheKey(), $this->metaData, $this->cacheTtl());
    }

    public function earliestTimestamp(): int
    {
        return $this->getMetaData('earliest_timestamp', 0);
    }

    public function latestTimestamp(): int
    {
        return $this->getMetaData('latest_timestamp', 0);
    }

    public function delete(): void
    {
        $this->clearCache();
        unlink($this->path);
        LogFileDeleted::dispatch($this);
    }
}
