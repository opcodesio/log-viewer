<?php

namespace Opcodes\LogViewer\Concerns\LogIndex;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Opcodes\LogViewer\LogIndexChunk;
use Opcodes\LogViewer\Utils\GenerateCacheKey;

trait CanCacheIndex
{
    public function clearCache(): void
    {
        $this->clearChunksFromCache();

        $this->cache()->forget($this->metaCacheKey());
        $this->cache()->forget($this->cacheKey());

        // this will reset all properties to default, because it won't find any cached settings for this index
        $this->loadMetadata();
    }

    protected function saveMetadataToCache(): void
    {
        $this->cache()->put($this->metaCacheKey(), $this->getMetadata(), $this->cacheTtl());
    }

    protected function getMetadataFromCache(): array
    {
        return $this->cache()->get($this->metaCacheKey(), []);
    }

    protected function saveChunkToCache(LogIndexChunk $chunk): void
    {
        $this->cache()->put(
            $this->chunkCacheKey($chunk->index),
            $chunk->data,
            $this->cacheTtl()
        );
    }

    protected function getChunkDataFromCache(int $index, $default = null): ?array
    {
        return $this->cache()->get($this->chunkCacheKey($index), $default);
    }

    protected function clearChunksFromCache(): void
    {
        foreach ($this->getChunkDefinitions() as $chunkDefinition) {
            $this->cache()->forget($this->chunkCacheKey($chunkDefinition['index']));
        }
    }

    protected function cacheKey(): string
    {
        return GenerateCacheKey::for($this);
    }

    protected function metaCacheKey(): string
    {
        return GenerateCacheKey::for($this, 'metadata');
    }

    protected function chunkCacheKey(int $index): string
    {
        return GenerateCacheKey::for($this, "chunk:$index");
    }

    protected function cache(): Repository
    {
        return app('log-viewer-cache');
    }

    protected function cacheTtl(): CarbonInterface
    {
        if (! empty($this->query)) {
            // There will be a lot more search queries, and they're usually just one-off searches.
            // We don't want these to take up too much of Redis/File-cache space for too long.
            return now()->addDay();
        }

        return now()->addWeek();
    }
}
