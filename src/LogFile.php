<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Cache;
use Opcodes\LogViewer\Events\LogFileDeleted;

class LogFile
{
    protected array $metaData;

    public function __construct(
        public string $name,
        public string $path,
    ) {
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
        $size = $this->size();

        if ($size > ($gb = 1024 * 1024 * 1024)) {
            return number_format($size / $gb, 2).' GB';
        } elseif ($size > ($mb = 1024 * 1024)) {
            return number_format($size / $mb, 2).' MB';
        } elseif ($size > ($kb = 1024)) {
            return number_format($size / $kb, 2).' KB';
        }

        return $size.' bytes';
    }

    public function downloadUrl(): string
    {
        return route('blv.download-file', $this->name);
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
        Cache::put($this->metaDataCacheKey(), $this->metaData, now()->addWeek());
    }

    public function getMetaData(string $attribute = null, $default = null): mixed
    {
        if (! isset($this->metaData)) {
            $this->metaData = Cache::get($this->metaDataCacheKey(), []);
            $this->metaDataChanged = false;
        }

        if (isset($attribute)) {
            return $this->metaData[$attribute] ?? $default;
        }

        return $this->metaData;
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
