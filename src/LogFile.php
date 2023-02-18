<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Arr;
use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;
use Opcodes\LogViewer\Utils\Utils;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogFile
{
    use Concerns\LogFile\HasMetadata;
    use Concerns\LogFile\CanCacheData;

    public string $path;

    public string $name;

    public string $identifier;

    public string $subFolder = '';

    private array $_logIndexCache;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->name = basename($path);
        $this->identifier = Utils::shortMd5($path).'-'.$this->name;

        // Let's remove the file name because we already know it.
        $this->subFolder = str_replace($this->name, '', $path);
        $this->subFolder = rtrim($this->subFolder, DIRECTORY_SEPARATOR);

        $this->loadMetadata();
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
        clearstatcache();

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
        return Utils::shortMd5($this->subFolder);
    }

    public function downloadUrl(): string
    {
        return route('log-viewer.files.download', $this->identifier);
    }

    public function download(): BinaryFileResponse
    {
        return response()->download($this->path);
    }

    public function addRelatedIndex(LogIndex $logIndex): void
    {
        $relatedIndices = collect($this->getMetadata('related_indices', []));
        $relatedIndices[$logIndex->identifier] = Arr::only(
            $logIndex->getMetadata(),
            ['query', 'last_scanned_file_position']
        );

        $this->setMetadata('related_indices', $relatedIndices->toArray());
    }

    public function getLastScannedFilePositionForQuery(?string $query = ''): ?int
    {
        foreach ($this->getMetadata('related_indices', []) as $indexIdentifier => $indexMetadata) {
            if ($query === $indexMetadata['query']) {
                return $indexMetadata['last_scanned_file_position'] ?? 0;
            }
        }

        return null;
    }

    public function mtime(): int
    {
        return is_file($this->path) ? filemtime($this->path) : 0;
    }

    public function earliestTimestamp(): int
    {
        return $this->getMetadata('earliest_timestamp') ?? $this->mtime();
    }

    public function latestTimestamp(): int
    {
        return $this->getMetadata('latest_timestamp') ?? $this->mtime();
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
