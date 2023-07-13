<?php

namespace Opcodes\LogViewer;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\Cache;
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
                break;
            default:
                rewind($this->fileHandle);
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

        // get the next log line
        [$text, $position] = match ($this->direction) {
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

        return $this->makeLog($text, $position);
    }

    protected function readLineForward(): array
    {
        $position = ftell($this->fileHandle);
        $line = fgets($this->fileHandle);

        return [$line, $position];
    }

    protected function readLineBackward(): array
    {
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

        return [$line, $position];
    }

    protected function getLogAtPosition(int $position): ?HttpLog
    {
        if ($this->isClosed()) {
            $this->open();
        }

        fseek($this->fileHandle, $position);

        list($text, $position) = $this->readLineForward();

        if ($text === false) {
            return null;
        }

        return $this->makeLog($text, $position);
    }

    protected function makeLog(string $text, int $filePosition): HttpLog
    {
        return match ($this->file->type) {
            LogFile::TYPE_HTTP_ACCESS => new HttpAccessLog($text, $this->file->identifier, $filePosition),
            LogFile::TYPE_HTTP_ERROR_APACHE => new HttpApacheErrorLog($text, $this->file->identifier, $filePosition),
            LogFile::TYPE_HTTP_ERROR_NGINX => new HttpNginxErrorLog($text, $this->file->identifier, $filePosition),
            default => $this->makeLogByGuessingType($text, $filePosition),
        };
    }

    protected function makeLogByGuessingType(string $text, int $filePosition): HttpLog
    {
        if (HttpAccessLog::matches($text)) {
            return new HttpAccessLog($text, $this->file->identifier, $filePosition);
        }

        if (HttpApacheErrorLog::matches($text)) {
            return new HttpApacheErrorLog($text, $this->file->identifier, $filePosition);
        }

        if (HttpNginxErrorLog::matches($text)) {
            return new HttpNginxErrorLog($text, $this->file->identifier, $filePosition);
        }

        throw new \Exception('Could not determine the log type for "'.$text.'".');
    }

    public function __destruct()
    {
        $this->close();
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
        return Cache::get('total-lines:'.$this->file->identifier, 0);
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

        // The only scanning we need to do here is to get the number of logs in total
        $shouldCloseAfterScanning = false;

        if ($this->isClosed()) {
            $this->open();
            $shouldCloseAfterScanning = true;
        }

        $this->forward()->reset();
        $numberOfLines = 0;

        while (fgets($this->fileHandle) !== false) {
            $numberOfLines++;
        }

        Cache::put('total-lines:'.$this->file->identifier, $numberOfLines);
        Cache::put('last-scanned-position:'.$this->file->identifier, ftell($this->fileHandle));

        if ($shouldCloseAfterScanning) {
            $this->close();
        } else {
            $this->reset();
        }

        return $this;
    }

    public function numberOfNewBytes(): int
    {
        $lastScannedPosition = Cache::get('last-scanned-position:'.$this->file->identifier, 0);

        return $this->file->size() - $lastScannedPosition;
    }

    public function requiresScan(): bool
    {
        return $this->numberOfNewBytes() > 0;
    }

    public function percentScanned(): int
    {
        return round($this->numberOfNewBytes() / $this->file->size() * 100);
    }
}
