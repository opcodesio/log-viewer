<?php

namespace Opcodes\LogViewer;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogLevels\LaravelLogLevel;
use Opcodes\LogViewer\Logs\BaseLog;
use Opcodes\LogViewer\Logs\LaravelLog;
use Opcodes\LogViewer\Utils\Utils;

class LogReader implements LogReaderInterface
{
    /**
     * Cached LogReader instances.
     */
    public static array $_instances = [];

    protected LogFile $file;

    protected string $logClass;

    protected string $levelClass;

    /**
     * Contains an index of file positions where each log entry is located in.
     */
    protected LogIndex $logIndex;

    protected ?int $limit = null;

    protected ?string $query = null;

    protected ?int $onlyShowIndex = null;

    protected bool $lazyScanning = false;

    /**
     * @var resource|null
     */
    protected $fileHandle = null;

    protected int $mtimeBeforeScan;

    protected string $direction = Direction::Forward;

    public function __construct(LogFile $file)
    {
        $this->file = $file;
        $this->logClass = LogTypeRegistrar::getClass($this->file->type());
        $this->levelClass = $this->logClass::levelClass();
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

    protected function index(): LogIndex
    {
        return $this->file->index($this->query);
    }

    /**
     * Load only the provided log levels
     *
     * @alias setLevels
     *
     * @param  string|array|null  $levels
     */
    public function only($levels = null): static
    {
        return $this->setLevels($levels);
    }

    /**
     * Load only the provided log levels
     *
     * @param  string|array|null  $levels
     */
    public function setLevels($levels = null): static
    {
        $this->index()->forLevels($levels);

        return $this;
    }

    public function allLevels(): static
    {
        return $this->setLevels(null);
    }

    /**
     * Load all log levels except the provided ones.
     *
     * @alias exceptLevels
     *
     * @param  string|array|null  $levels
     */
    public function except($levels = null): static
    {
        return $this->exceptLevels($levels);
    }

    /**
     * Load all log levels except the provided ones.
     *
     * @param  string|array|null  $levels
     */
    public function exceptLevels($levels = null): static
    {
        $this->index()->exceptLevels($levels);

        return $this;
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

        if ($this->requiresScan() && ! $this->lazyScanning) {
            $this->scan();
        } else {
            $this->reset();
        }

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

    public function reverse(): static
    {
        $this->direction = Direction::Backward;
        $this->index()->reverse();

        return $this->reset();
    }

    public function forward(): static
    {
        $this->direction = Direction::Forward;
        $this->index()->forward();

        return $this->reset();
    }

    public function setDirection(string $direction = null): static
    {
        $this->direction = $direction === Direction::Backward
            ? Direction::Backward
            : Direction::Forward;
        $this->index()->setDirection($this->direction);

        return $this;
    }

    public function skip(int $number): static
    {
        $this->index()->skip($number);

        return $this;
    }

    protected function onlyShow(int $targetIndex = 0): static
    {
        $this->onlyShowIndex = $targetIndex;

        return $this;
    }

    public function limit(int $number): static
    {
        $this->index()->limit($number);

        return $this;
    }

    public function search(string $query = null): static
    {
        return $this->setQuery($query);
    }

    protected function setQuery(string $query = null): static
    {
        $this->close();

        if (! empty($query) && Str::startsWith($query, 'log-index:')) {
            $this->query = null;
            $this->only(null);
            $this->onlyShow(intval(explode(':', $query)[1]));
        } elseif (! empty($query)) {
            $query = '~'.$query.'~i';

            Utils::validateRegex($query);

            $this->query = $query;
        } else {
            $this->query = null;
        }

        $this->index()->setQuery($this->query);

        return $this;
    }

    public function lazyScanning($lazy = true): static
    {
        $this->lazyScanning = $lazy;

        return $this;
    }

    /**
     * This method scans the whole file quickly to index the logs in order to speed up
     * the retrieval of individual logs
     *
     * @throws \Exception
     */
    public function scan(int $maxBytesToScan = null, bool $force = false): static
    {
        if (is_null($maxBytesToScan)) {
            $maxBytesToScan = LogViewer::lazyScanChunkSize();
        }

        if ($this->isClosed()) {
            $this->open();
        }

        if (! $this->requiresScan() && ! $force) {
            return $this;
        }

        if ($this->numberOfNewBytes() < 0) {
            // the file reduced in size... something must've gone wrong, so let's
            // force a full re-index.
            $force = true;
        }

        if ($force) {
            // when forcing, make sure we start from scratch and reset everything.
            $this->index()->clearCache();
        }

        $stopScanningAfter = microtime(true) + LogViewer::lazyScanTimeout();
        $this->mtimeBeforeScan = $this->file->mtime();

        // we don't care about the selected levels here, we should scan everything
        $logIndex = $this->index();
        $laravelSeverityLevels = LaravelLogLevel::caseValues();
        $earliest_timestamp = $this->file->getMetadata('earliest_timestamp');
        $latest_timestamp = $this->file->getMetadata('latest_timestamp');
        $currentLog = '';
        $currentLogLevel = '';
        $currentTimestamp = null;
        $currentIndex = $this->index()->getLastScannedIndex();
        fseek($this->fileHandle, $this->index()->getLastScannedFilePosition());
        $currentLogPosition = ftell($this->fileHandle);
        $lastPositionToScan = isset($maxBytesToScan) ? ($currentLogPosition + $maxBytesToScan) : null;

        while (
            (! isset($lastPositionToScan) || $currentLogPosition < $lastPositionToScan)
            && ($stopScanningAfter > microtime(true))
            && ($line = fgets($this->fileHandle, 1024)) !== false
        ) {
            /**
             * $matches[0] - the full line being checked
             * $matches[1] - the full timestamp in-between the square brackets, including the optional microseconds
             *               and the optional timezone offset
             * $matches[2] - the optional microseconds
             * $matches[3] - the optional timezone offset, like `+02:00` or `-05:30`
             */
            $matches = [];
            $ts = null;
            $lvl = null;
            if ($this->logClass::matches(trim($line), $ts, $lvl)) {
                if ($currentLog !== '') {
                    if (is_null($this->query) || preg_match($this->query, $currentLog)) {
                        $logIndex->addToIndex($currentLogPosition, $currentTimestamp ?? 0, $currentLogLevel, $currentIndex);
                    }

                    $currentLog = '';
                    $currentIndex++;
                }

                $currentTimestamp = $ts;
                $earliest_timestamp = min($earliest_timestamp ?? $currentTimestamp, $currentTimestamp);
                $latest_timestamp = max($latest_timestamp ?? $currentTimestamp, $currentTimestamp);
                $currentLogPosition = ftell($this->fileHandle) - strlen($line);
                $currentLogLevel = $lvl;

                if ($this->logClass === LaravelLog::class) {
                    $lowercaseLine = strtolower($line);

                    foreach ($laravelSeverityLevels as $level) {
                        if (strpos($lowercaseLine, '.'.$level) || strpos($lowercaseLine, $level.':')) {
                            $currentLogLevel = $level;
                            break;
                        }
                    }
                }

                // Because we matched this line as the beginning of a new log,
                // and we have already processed the previously set $currentLog variable,
                // we can safely set this to the current line we scanned.
                $currentLog = $line;
            } elseif ($currentLog !== '') {
                // This check makes sure we don't keep adding rubbish content to the log
                // if we haven't found a proper matching beginning of a log entry yet.
                // So any content (empty lines, unrelated text) at the beginning of the log file
                // will be ignored until the first matching log entry comes up.
                $currentLog .= $line;
            }
        }

        if ($currentLog !== '' && $this->logClass::matches($currentLog)) {
            if ((is_null($this->query) || preg_match($this->query, $currentLog))) {
                $logIndex->addToIndex($currentLogPosition, $currentTimestamp ?? 0, $currentLogLevel, $currentIndex);
                $currentIndex++;
            }
        }

        $logIndex->setLastScannedIndex($currentIndex);
        $logIndex->setLastScannedFilePosition(ftell($this->fileHandle));
        $logIndex->save();

        $this->file->setMetadata('name', $this->file->name);
        $this->file->setMetadata('path', $this->file->path);
        $this->file->setMetadata('size', $this->file->size());
        $this->file->setMetadata('earliest_timestamp', $this->index()->getEarliestTimestamp());
        $this->file->setMetadata('latest_timestamp', $this->index()->getLatestTimestamp());
        $this->file->setMetadata('last_scanned_file_position', ftell($this->fileHandle));
        $this->file->addRelatedIndex($logIndex);

        $this->file->saveMetadata();

        // Let's reset the position in preparation for real log reads.
        rewind($this->fileHandle);

        return $this->reset();
    }

    public function reset(): static
    {
        $this->index()->reset();

        return $this;
    }

    /**
     * @return array|LevelCount[]
     *
     * @throws \Exception
     */
    public function getLevelCounts(): array
    {
        if ($this->isClosed()) {
            $this->open();
        }

        $selectedLevels = $this->index()->getSelectedLevels();
        $exceptedLevels = $this->index()->getExceptedLevels();
        $levelClass = $this->logClass::levelClass();

        return $this->index()->getLevelCounts()->map(function (int $count, string $level) use ($selectedLevels, $exceptedLevels) {
            return new LevelCount(
                $this->levelClass::from($level),
                $count,
                (is_null($selectedLevels) || in_array($level, $selectedLevels))
                && (is_null($exceptedLevels) || ! in_array($level, $exceptedLevels))
            );
        })->sortBy(fn (LevelCount $levelCount) => $levelCount->level->getName(), SORT_NATURAL)->toArray();
    }

    /**
     * @return array|BaseLog[]
     */
    public function get(int $limit = null): array
    {
        if (! is_null($limit)) {
            $this->limit($limit);
        }

        $logs = [];

        while ($log = $this->next()) {
            $logs[] = $log;
        }

        return $logs;
    }

    protected function getLogAtIndex(int $index): ?BaseLog
    {
        $position = $this->index()->getPositionForIndex($index);

        [$text, $position] = $this->getLogText($index, $position);

        // If we did not find any logs, this means either the file is empty, or
        // we have already reached the end of file. So we return early.
        if ($text === '') {
            return null;
        }

        return $this->makeLog($text, $position, $index);
    }

    public function next(): ?BaseLog
    {
        // We open it here to make we also check for possible need of index re-building.
        if ($this->isClosed()) {
            $this->open();
        }

        [$index, $position] = $this->index()->next();

        if (is_null($index)) {
            return null;
        }

        [$text, $position] = $this->getLogText($index, $position);

        if (empty($text)) {
            return null;
        }

        return $this->makeLog($text, $position, $index);
    }

    public function total(): int
    {
        return $this->index()->count();
    }

    /**
     * Alias for total()
     */
    public function count(): int
    {
        return $this->total();
    }

    public function paginate(int $perPage = 25, int $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage('page');

        if (! is_null($this->onlyShowIndex)) {
            return new LengthAwarePaginator(
                [$this->reset()->getLogAtIndex($this->onlyShowIndex)],
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

    protected function makeLog(string $text, int $filePosition, int $index): BaseLog
    {
        return new $this->logClass($text, $this->file->identifier, $filePosition, $index);
    }

    /**
     * @return array|null Returns an array, [$level, $text, $position]
     *
     * @throws \Exception
     */
    protected function getLogText(int $index, int $position, bool $fullText = false): ?array
    {
        if ($this->isClosed()) {
            $this->open();
        }

        fseek($this->fileHandle, $position, SEEK_SET);

        $currentLog = '';

        while (($line = fgets($this->fileHandle)) !== false) {
            if ($this->logClass::matches($line)) {
                if ($currentLog !== '') {
                    // found the next log, so let's stop the loop and return the log we found
                    break;
                }
            }

            $currentLog .= $line;
        }

        return [$currentLog, $position];
    }

    public function numberOfNewBytes(): int
    {
        $lastScannedFilePosition = $this->file->getLastScannedFilePositionForQuery($this->query);

        if (is_null($lastScannedFilePosition)) {
            $lastScannedFilePosition = $this->index()->getLastScannedFilePosition();
        }

        return $this->file->size() - $lastScannedFilePosition;
    }

    public function requiresScan(): bool
    {
        if (isset($this->mtimeBeforeScan) && ($this->file->mtime() > $this->mtimeBeforeScan || $this->file->mtime() === time())) {
            // The file has been modified since the last scan in this request.
            // Let's only request another scan if it's not the last chunk (smaller than lazyScanChunkSize).
            // The last chunk will be scanned until the end before hitting this logic again,
            // and by then the only appended bytes will be from the current request and thus return false.
            return $this->numberOfNewBytes() >= LogViewer::lazyScanChunkSize();
        }

        return $this->numberOfNewBytes() !== 0;
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
