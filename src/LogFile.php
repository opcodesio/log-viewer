<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Utils\Utils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogFile
{
    use Concerns\LogFile\HasMetadata;
    use Concerns\LogFile\CanCacheData;

    public string $path;

    public string $name;

    public string $identifier;

    public string $absolutePath = '';

    public string $subFolder = '';

    private array $_logIndexCache;

    public function __construct(string $path)
    {
        $pathInfo = pathinfo($path);
        $this->path = $path;
        $this->name = $pathInfo['basename'];
        $this->identifier = Str::substr(md5($path), -8, 8).'-'.$this->name;

        // Let's remove the file name because we already know it.
        $this->subFolder = str_replace($this->name, '', $path);
        $this->subFolder = rtrim($this->subFolder, DIRECTORY_SEPARATOR);

//        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
//            $this->absolutePath = pathinfo($path)['dirname'];
//            $this->path = pathinfo($path)['basename'];
//        }

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
        return LogViewer::getFilesystem()->exists($this->path)
            ? LogViewer::getFilesystem()->size($this->path)
            : 0;
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

    public function download(): StreamedResponse
    {
        return LogViewer::getFilesystem($this->absolutePath)->download($this->path);
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

    public function earliestTimestamp(): int
    {
        return $this->getMetadata('earliest_timestamp')
            ?? LogViewer::getFilesystem($this->absolutePath)->exists($this->path) ? LogViewer::getFilesystem($this->absolutePath)->lastModified($this->path) : 0;
    }

    public function latestTimestamp(): int
    {
        return $this->getMetadata('latest_timestamp')
            ?? LogViewer::getFilesystem($this->absolutePath)->exists($this->path) ? LogViewer::getFilesystem($this->absolutePath)->lastModified($this->path) : 0;
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
        if (LogViewer::getFilesystem($this->absolutePath)->exists($this->path)) {
            LogViewer::getFilesystem($this->absolutePath)->delete($this->path);
        }
        LogFileDeleted::dispatch($this);
    }
}
