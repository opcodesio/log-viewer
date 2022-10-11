<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Utils\Utils;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogFile
{
    public string $identifier;

    public string $subFolder = '';

    protected array $metaData;

    private array $_logIndexCache;

    public function __construct(
        public string $name,
        public string $path,
    ) {
        $this->identifier = Str::substr(md5($path), -8, 8).'-'.$name;

        // Let's remove the file name because we already know it.
        $this->subFolder = str_replace($name, '', $path);
        $this->subFolder = rtrim($this->subFolder, DIRECTORY_SEPARATOR);

        $this->metaData = Cache::get($this->metaDataCacheKey(), []);
    }

    public static function fromPath(string $filePath): LogFile
    {
        return new self(
            basename($filePath),
            $filePath,
        );
    }

    public function index(string $query = null): LogIndex
    {
        if (! isset($this->_logIndexCache[$query])) {
            $this->_logIndexCache[$query] = new LogIndex($this, $query);
        }

        return $this->_logIndexCache[$query];
    }

    public function logs(): LogReader
    {
        return LogReader::instance($this);
    }

    public function size(): int
    {
        return filesize($this->path);
    }

    public function sizeInMB(): float
    {
        return $this->size() / 1024 / 1024;
    }

    public function sizeFormatted(): string
    {
        return Utils::bytesForHumans($this->size());
    }

    public function subFolderIdentifier(): string
    {
        return Str::substr(md5($this->subFolder), -8, 8);
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

    public function cacheKey(): string
    {
        return 'log-viewer:'.LogViewer::version().':file:'.md5($this->path);
    }

    public function addRelatedIndex(LogIndex $logIndex): void
    {
        $relatedIndices = collect($this->getMetaData('related_indices', []));
        $relatedIndices[$logIndex->identifier()] = Arr::only(
            $logIndex->getMetadata(),
            ['query', 'last_scanned_file_position']
        );

        $this->setMetaData('related_indices', $relatedIndices->toArray());
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
        return array_merge(
            Cache::get($this->relatedCacheKeysKey(), []),
            [
                $this->indexCacheKeyForQuery(),
                $this->indexCacheKeyForQuery().':last-scan',
            ]
        );
    }

    protected function indexCacheKeyForQuery(string $query = ''): string
    {
        return $this->cacheKey().':'.md5($query).':index';
    }

    public function clearCache(): void
    {
        foreach ($this->getMetaData('related_indices', []) as $indexIdentifier => $indexMetadata) {
            $this->index($indexMetadata['query'])->clearCache();
        }

        foreach ($this->getRelatedCacheKeys() as $relatedCacheKey) {
            Cache::forget($relatedCacheKey);
        }

        Cache::forget($this->metaDataCacheKey());
        Cache::forget($this->relatedCacheKeysKey());

        $this->index()->clearCache();
    }

    public function getLastScannedFilePositionForQuery(?string $query = ''): ?int
    {
        foreach ($this->getMetaData('related_indices', []) as $indexIdentifier => $indexMetadata) {
            if ($query === $indexMetadata['query']) {
                return $indexMetadata['last_scanned_file_position'] ?? 0;
            }
        }

        return null;
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
        return $this->getMetaData('earliest_timestamp')
            ?? (is_file($this->path) ? filemtime($this->path) : 0);
    }

    public function latestTimestamp(): int
    {
        return $this->getMetaData('latest_timestamp')
            ?? (is_file($this->path) ? filemtime($this->path) : 0);
    }

    public function scan(int $maxBytesToScan = null, bool $force = false): void
    {
        $this->logs()->scan($maxBytesToScan, $force);
    }

    public function requiresScan(): bool
    {
        return $this->logs()->requiresScan();
    }

    /**
     * @throws InvalidRegularExpression
     */
    public function search(string $query = null): LogReader
    {
        return $this->logs()->search($query);
    }

    public function delete(): void
    {
        $this->clearCache();
        unlink($this->path);
        LogFileDeleted::dispatch($this);
    }
}
