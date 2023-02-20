<?php

namespace Opcodes\LogViewer\Concerns\LogFile;

use Carbon\CarbonInterface;
use Opcodes\LogViewer\Facades\Cache;
use Opcodes\LogViewer\Utils\GenerateCacheKey;
use Opcodes\LogViewer\Utils\Utils;

trait CanCacheData
{
    protected function indexCacheKeyForQuery(string $query = ''): string
    {
        return GenerateCacheKey::for($this, Utils::shortMd5($query).':index');
    }

    public function clearCache(): void
    {
        foreach ($this->getMetadata('related_indices', []) as $indexIdentifier => $indexMetadata) {
            $this->index($indexMetadata['query'])->clearCache();
        }

        foreach ($this->getRelatedCacheKeys() as $relatedCacheKey) {
            Cache::forget($relatedCacheKey);
        }

        Cache::forget($this->metadataCacheKey());
        Cache::forget($this->relatedCacheKeysKey());

        $this->index()->clearCache();
    }

    protected function cacheTtl(): CarbonInterface
    {
        return now()->addWeek();
    }

    protected function cacheKey(): string
    {
        return GenerateCacheKey::for($this);
    }

    protected function relatedCacheKeysKey(): string
    {
        return GenerateCacheKey::for($this, 'related-cache-keys');
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

    protected function getRelatedCacheKeys(): array
    {
        return array_merge(
            Cache::get($this->relatedCacheKeysKey(), []),
            [
                $this->indexCacheKeyForQuery(),
                $this->indexCacheKeyForQuery().':last-scan',
            ]
        );
    }

    protected function metadataCacheKey(): string
    {
        return GenerateCacheKey::for($this, 'metadata');
    }

    protected function loadMetadataFromCache(): array
    {
        return Cache::get($this->metadataCacheKey(), []);
    }

    protected function saveMetadataToCache(array $data): void
    {
        Cache::put($this->metadataCacheKey(), $data, $this->cacheTtl());
    }
}
