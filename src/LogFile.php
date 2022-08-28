<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Cache;
use Opcodes\LogViewer\Events\LogFileDeleted;

class LogFile
{
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
