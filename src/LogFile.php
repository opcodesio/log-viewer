<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Arr;
use Opcodes\LogViewer\Events\LogFileDeleted;
use Opcodes\LogViewer\Exceptions\CannotOpenFileException;
use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Logs\LogType;
use Opcodes\LogViewer\Readers\LogReaderInterface;
use Opcodes\LogViewer\Utils\Utils;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogFile
{
    use Concerns\LogFile\CanCacheData;
    use Concerns\LogFile\HasMetadata;

    public string $path;
    public string $name;
    public string $identifier;
    public string $subFolder = '';
    private ?string $type = null;
    private array $_logIndexCache;

    public function __construct(string $path, ?string $type = null)
    {
        $this->path = $path;
        $this->name = basename($path);
        $this->identifier = Utils::shortMd5($path).'-'.$this->name;
        $this->type = $type;

        // Let's remove the file name because we already know it.
        $this->subFolder = str_replace($this->name, '', $path);
        $this->subFolder = rtrim($this->subFolder, DIRECTORY_SEPARATOR);

        $this->loadMetadata();
    }

    public function type(): LogType
    {
        if (is_null($this->type)) {
            $this->type = $this->getMetadata('type');
        }

        if (is_null($this->type)) {
            // can we first guess it by the file name?
            $this->type = app(LogTypeRegistrar::class)->guessTypeFromFileName($this);

            if (is_null($this->type)) {
                $this->type = app(LogTypeRegistrar::class)->guessTypeFromFirstLine($this);
            }

            $this->setMetadata('type', $this->type);
            $this->saveMetadata();
        }

        return new LogType($this->type ?? LogType::DEFAULT);
    }

    public function index(?string $query = null): LogIndex
    {
        if (! isset($this->_logIndexCache[$query])) {
            $this->_logIndexCache[$query] = new LogIndex($this, $query);
        }

        return $this->_logIndexCache[$query];
    }

    public function logs(): LogReaderInterface
    {
        $logReaderClass = LogViewer::logReaderClass();

        return $logReaderClass::instance($this);
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

    public function contents(): string
    {
        return file_get_contents($this->path);
    }

    public function getFirstLine(): string
    {
        return $this->getNthLine(1);
    }

    public function getNthLine(int $lineNumber): string
    {
        try {
            $handle = fopen($this->path, 'r');
        } catch (\ErrorException $e) {
            throw new CannotOpenFileException('Could not open "'.$this->path.'" for reading.', 0, $e);
        }

        $line = '';
        for ($i = 0; $i < $lineNumber; $i++) {
            $line = fgets($handle);
        }
        fclose($handle);

        return $line;
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

    public function scan(?int $maxBytesToScan = null, bool $force = false): void
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
    public function search(?string $query = null): LogReaderInterface
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
