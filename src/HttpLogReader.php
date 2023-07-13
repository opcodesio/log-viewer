<?php

namespace Opcodes\LogViewer;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Utils\Utils;

class HttpLogReader implements LogReaderInterface
{
    /**
     * Cached LogReader instances.
     */
    public static array $_instances = [];

    protected LogFile $file;

    /** @var resource|null */
    protected $fileHandle = null;

    protected string $direction = Direction::Forward;

    protected ?array $filterLevels = null;

    protected ?int $limit = null;

    protected int $skip = 0;

    protected ?string $query = null;

    private ?int $onlyShowPosition = null;

    private ?int $currentLogIndex = null;

    public function __construct(LogFile $file)
    {
        $this->file = $file;
    }

    public static function instance(LogFile $file): static
    {
        if (! isset(self::$_instances[$file->path])) {
            self::$_instances[$file->path] = new self($file);
        }

        return self::$_instances[$file->path];
    }

    public static function clearInstance(LogFile $file): void
    {
        if (isset(self::$_instances[$file->path])) {
            unset(self::$_instances[$file->path]);
        }
    }

    public static function clearInstances(): void
    {
        self::$_instances = [];
    }

    protected function isOpen(): bool
    {
        return is_resource($this->fileHandle);
    }

    protected function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    /**
     * Open the log file for reading. Most other methods will open the file automatically if needed.
     *
     * @throws \Exception
     */
    protected function open(): static
    {
        if ($this->isOpen()) {
            return $this;
        }

        $this->fileHandle = fopen($this->file->path, 'r');

        if ($this->fileHandle === false) {
            throw new \Exception('Could not open "'.$this->file->path.'" for reading.');
        }

        $this->resetFilePointer();

        return $this;
    }

    /**
     * Close the file handle.
     *
     * @throws \Exception
     */
    protected function close(): static
    {
        if ($this->isClosed()) {
            return $this;
        }

        if (fclose($this->fileHandle)) {
            $this->fileHandle = null;
        } else {
            throw new \Exception('Could not close the file "'.$this->file->path.'".');
        }

        return $this;
    }

    public function skip(int $number): static
    {
        $this->skip = $number;

        return $this;
    }

    public function limit(int $number): static
    {
        $this->limit = $number;

        return $this;
    }

    public function reverse(): static
    {
        $this->direction = Direction::Backward;

        return $this->reset();
    }

    public function forward(): static
    {
        $this->direction = Direction::Forward;

        return $this->reset();
    }

    public function setDirection(string $direction = null): static
    {
        $this->direction = $direction;

        return $this->reset();
    }

    public function reset(): static
    {
        if ($this->isClosed()) {
            return $this;
        }

        $this->resetFilePointer();

        return $this;
    }

    protected function resetFilePointer(): void
    {
        switch ($this->direction) {
            case Direction::Backward:
                fseek($this->fileHandle, 0, SEEK_END);
                $this->currentLogIndex = $this->file->getMetadata('last_scanned_index', 0);
                break;
            default:
                rewind($this->fileHandle);
                $this->currentLogIndex = 0;
                break;
        }
    }

    /**
     * @return array|HttpLog[]
     */
    public function get(int $limit = null): array
    {
        if (! is_null($limit)) {
            $this->limit($limit);
        }

        $logs = [];
        $entries = 0;

        while ($log = $this->next()) {
            $logs[] = $log;
            $entries++;

            if (isset($this->limit) && $entries >= $this->limit) {
                break;
            }
        }

        return $logs;
    }

    public function next(): ?HttpLog
    {
        if ($this->isClosed()) {
            $this->open();
        }

        if ($this->skip >= 1000) {
            // we can fast-forward to another known chunked position.
            $this->fastForwardSkippedEntries();
        }

        // get the next log line
        [$text, $position, $index] = match ($this->direction) {
            Direction::Forward => $this->readLineForward(),
            Direction::Backward => $this->readLineBackward(),
            default => throw new \Exception('Unknown direction: '.$this->direction),
        };

        if ($text === false) {
            return null;
        }

        if ($this->skip > 0) {
            $this->skip--;

            return $this->next();
        }

        return $this->makeLog($text, $position, $index);
    }

    protected function readLineForward(): array
    {
        $index = $this->currentLogIndex;
        $position = ftell($this->fileHandle);
        $line = fgets($this->fileHandle);

        // set it up for the next read
        $this->currentLogIndex++;

        return [$line, $position, $index];
    }

    protected function readLineBackward(): array
    {
        $index = $this->currentLogIndex;
        $line = false;
        $position = null;

        while (true) {
            if (ftell($this->fileHandle) <= 0) {
                return [$line, 0];
            }

            fseek($this->fileHandle, -1, SEEK_CUR);
            $char = fgetc($this->fileHandle);

            if ($char === "\n" && $line === false) {
                // we have not yet started reading a line, so skip this character
                fseek($this->fileHandle, -1, SEEK_CUR);

                continue;
            }

            if ($char === "\n") {
                $position = ftell($this->fileHandle);
                fseek($this->fileHandle, -1, SEEK_CUR);
                break;
            }

            $line = $char.($line ?: '');
            fseek($this->fileHandle, -1, SEEK_CUR);
        }

        $this->currentLogIndex--;

        return [$line, $position, $index];
    }

    protected function fastForwardSkippedEntries(): void
    {
        if ($this->skip < 1000) {
            return;
        }

        $lazyIndex = $this->file->getMetadata('lazy_index', []);
        $currentIndex = $this->currentLogIndex;

        if ($this->direction === Direction::Forward) {
            $potentialIndex = intval(floor(($currentIndex + $this->skip) / 1000)) * 1000;
            $potentialPosition = $lazyIndex[$potentialIndex] ?? null;

            if (is_null($potentialPosition)) {
                throw new \Exception('Could not find a known position for index '.$potentialIndex);
            }

            $skipEntries = $potentialIndex - $currentIndex;
            $this->skip -= $skipEntries;
            $this->currentLogIndex += $skipEntries;
            fseek($this->fileHandle, $potentialPosition);

        } else {
            // backwards!

            $potentialIndex = intval(ceil(($currentIndex - $this->skip) / 1000)) * 1000;
            $potentialPosition = $lazyIndex[$potentialIndex] ?? null;

            if (is_null($potentialPosition)) {
                throw new \Exception('Could not find a known position for index '.$potentialIndex);
            }

            $skipEntries = $currentIndex - $potentialIndex;
            $this->skip -= $skipEntries;
            $this->currentLogIndex -= $skipEntries;
            fseek($this->fileHandle, $potentialPosition);
        }
    }

    protected function getLogAtPosition(int $position): ?HttpLog
    {
        if ($this->isClosed()) {
            $this->open();
        }

        fseek($this->fileHandle, $position);

        [$text, $position] = $this->readLineForward();

        if ($text === false) {
            return null;
        }

        return $this->makeLog($text, $position);
    }

    protected function makeLog(string $text, int $filePosition, int $index = null): HttpLog
    {
        return match ($this->file->type) {
            LogFile::TYPE_HTTP_ACCESS => new HttpAccessLog($text, $this->file->identifier, $filePosition, $index),
            LogFile::TYPE_HTTP_ERROR_APACHE => new HttpApacheErrorLog($text, $this->file->identifier, $filePosition, $index),
            LogFile::TYPE_HTTP_ERROR_NGINX => new HttpNginxErrorLog($text, $this->file->identifier, $filePosition, $index),
            default => $this->makeLogByGuessingType($text, $filePosition, $index),
        };
    }

    protected function makeLogByGuessingType(string $text, int $filePosition, int $index = null): HttpLog
    {
        if (HttpAccessLog::matches($text)) {
            return new HttpAccessLog($text, $this->file->identifier, $filePosition, $index);
        }

        if (HttpApacheErrorLog::matches($text)) {
            return new HttpApacheErrorLog($text, $this->file->identifier, $filePosition, $index);
        }

        if (HttpNginxErrorLog::matches($text)) {
            return new HttpNginxErrorLog($text, $this->file->identifier, $filePosition, $index);
        }

        throw new \Exception('Could not determine the log type for "'.$text.'".');
    }

    protected function onlyShowAtPosition(int $targetPosition = 0): static
    {
        $this->onlyShowPosition = $targetPosition;

        return $this;
    }

    public function search(string $query = null): static
    {
        $this->close();

        if (! empty($query) && Str::startsWith($query, 'file-pos:')) {
            $this->query = null;
            $this->only(null);
            $this->onlyShowAtPosition(intval(explode(':', $query)[1]));
        } elseif (! empty($query)) {
            $query = '/'.$query.'/i';

            Utils::validateRegex($query);

            $this->query = $query;
        } else {
            $this->query = null;
        }

        return $this;
    }

    public function only($levels = null): static
    {
        return $this->setLevels($levels);
    }

    public function setLevels($levels = null): static
    {
        if (is_string($levels)) {
            $levels = [$levels];
        }

        if (is_array($levels)) {
            $this->filterLevels = array_map('strtolower', array_filter($levels));
        } else {
            $this->filterLevels = null;
        }

        return $this;
    }

    public function allLevels(): static
    {
        return $this->setLevels(null);
    }

    public function except($levels = null): static
    {
        return $this->exceptLevels($levels);
    }

    public function exceptLevels($levels = null): static
    {
        if (is_array($levels)) {
            $levels = array_map('strtolower', array_filter($levels));
            $levels = array_diff(self::getDefaultLevels(), $levels);
        } elseif (is_string($levels)) {
            $level = strtolower($levels);
            $levels = array_diff(self::getDefaultLevels(), [$level]);
        }

        return $this->setLevels($levels);
    }

    public static function getDefaultLevels(): array
    {
        return [];
    }

    public function supportsLevels(): bool
    {
        return false;
    }

    public function getLevelCounts(): array
    {
        return [];
    }

    public function total(): int
    {
        return $this->file->getMetadata('last_scanned_index', 0);
    }

    public function count(): int
    {
        return $this->total();
    }

    public function paginate(int $perPage = 25, int $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage('page');

        if (! is_null($this->onlyShowPosition)) {
            return new LengthAwarePaginator(
                [$this->reset()->getLogAtPosition($this->onlyShowPosition)],
                1,
                $perPage,
                $page
            );
        }

        $this->reset()->skip(max(0, $page - 1) * $perPage);

        return new LengthAwarePaginator(
            $this->get($perPage),
            $this->total(),
            $perPage,
            $page
        );
    }

    public function scan(int $maxBytesToScan = null, bool $force = false): static
    {
        if (! $this->requiresScan() && ! $force) {
            return $this;
        }

        if ($this->isClosed()) {
            $this->open();
        }

        if ($this->numberOfNewBytes() < 0) {
            // the file reduced in size... something must've gone wrong, so let's
            // force a full re-index.
            $force = true;
        }

        if ($force) {
            // when forcing, make sure we start from scratch and reset everything.
            $this->file->clearCache();
        }

        $this->forward()->reset();
        $lineIndex = $this->file->getMetadata('last_scanned_index', 0);
        $linePosition = $this->file->getMetadata('last_scanned_file_position', 0);
        fseek($this->fileHandle, $linePosition);
        $bytesScanned = 0;
        $lazyIndex = $this->file->getMetadata('lazy_index', []);

        while (($line = fgets($this->fileHandle)) !== false) {
            if ($lineIndex % 1000 === 0) {
                // every 1000th line, we'll save the current position
                $lazyIndex[$lineIndex] = $linePosition;
            }

            $length = strlen($line);
            $linePosition += $length;
            $bytesScanned += $length;
            $lineIndex++;

            if ($bytesScanned >= $maxBytesToScan) {
                break;
            }
        }

        $this->file->setMetadata('name', $this->file->name);
        $this->file->setMetadata('path', $this->file->path);
        $this->file->setMetadata('size', $this->file->size());
        $this->file->setMetadata('last_scanned_file_position', ftell($this->fileHandle));
        $this->file->setMetadata('last_scanned_index', $lineIndex);
        $this->file->setMetadata('lazy_index', $lazyIndex);
        $this->file->saveMetadata();

        $this->reset();

        return $this;
    }

    public function numberOfNewBytes(): int
    {
        $lastScannedPosition = $this->file->getMetadata('last_scanned_file_position', 0);

        return $this->file->size() - $lastScannedPosition;
    }

    public function requiresScan(): bool
    {
        return $this->numberOfNewBytes() > 0;
    }

    public function percentScanned(): int
    {
        if ($this->file->size() <= 0) {
            // empty file, so assume it has been fully scanned.
            return 100;
        }

        return 100 - intval(($this->numberOfNewBytes() / $this->file->size() * 100));
    }

    public function __destruct()
    {
        $this->close();
    }
}
