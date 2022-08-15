<?php

namespace Opcodes\LogViewer;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Concerns\HasLocalCache;
use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;

class LogReader
{
    use HasLocalCache;

    const LOG_MATCH_PATTERN = '/\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d{6}[\+-]\d\d:\d\d)?\].*/';

    const DIRECTION_FORWARD = 'forward';

    const DIRECTION_BACKWARD = 'backward';

    /**
     * Cached LogReader instances.
     *
     * @var array
     */
    public static array $_instances = [];

    protected array $_mergedIndex;

    /**
     * @var LogFile
     */
    protected LogFile $file;

    /**
     * Whether the index has been updated. Used to check whether we should write
     * the new changes to the cache.
     *
     * @var bool
     */
    protected bool $indexChanged = false;

    /**
     * Contains an index of file positions where each log is located in.
     *
     * @var array
     */
    public array $logIndex = [];

    /**
     * File size when it was last indexed.
     *
     * @var int
     */
    protected int $lastScanFileSize = 0;

    /**
     * The log levels that should be read from this file.
     *
     * @var array|null
     */
    protected ?array $levels = null;

    protected ?int $limit = null;

    protected ?string $query = null;

    protected ?int $onlyShowIndex = null;

    /**
     * The index of the next log to be read
     *
     * @var int
     */
    protected int $nextLogIndex = 0;

    /**
     * @var resource|null
     */
    protected $fileHandle = null;

    protected string $direction = self::DIRECTION_FORWARD;

    public function __construct(LogFile $file)
    {
        $this->file = $file;
    }

    public static function instance(LogFile $file): self
    {
        if (! isset(self::$_instances[$file->path])) {
            self::$_instances[$file->path] = new self($file);
        }

        return self::$_instances[$file->path];
    }

    /**
     * Load only the provided log levels
     *
     * @param  string|array|null  $levels
     * @return $this
     */
    public function only($levels = null): self
    {
        if (is_array($levels)) {
            $this->levels = [];
            $defaultLevels = self::getDefaultLevels();
            $levels = array_map('strtolower', $levels);

            foreach ($levels as $level) {
                if (in_array($level, $defaultLevels)) {
                    $this->levels[] = $level;
                }
            }
        } elseif (is_string($levels)) {
            $level = strtolower($levels);

            if (in_array($level, self::getDefaultLevels())) {
                $this->levels = [$level];
            }
        } else {
            $this->levels = null;
        }

        unset($this->_mergedIndex);

        return $this;
    }

    /**
     * Load all log levels except the provided ones.
     *
     * @param  string|array|null  $levels
     * @return $this
     */
    public function except($levels = null): self
    {
        if (is_array($levels)) {
            $levels = array_map('strtolower', $levels);
            $this->levels = array_diff(self::getDefaultLevels(), $levels);
        } elseif (is_string($levels)) {
            $level = strtolower($levels);
            $this->levels = array_diff(self::getDefaultLevels(), [$level]);
        } else {
            $this->levels = null;
        }

        return $this;
    }

    public function getIndexCacheKey(): string
    {
        return 'log-viewer:log-index:'.implode(':', [
            $this->file->name,
            md5($this->query ?? ''),
        ]);
    }

    public function getSelectedLevels(): array
    {
        if (is_array($this->levels)) {
            return $this->levels;
        }

        return self::getDefaultLevels();
    }

    public static function getDefaultLevels(): array
    {
        return Level::caseValues();
    }

    public function isOpen(): bool
    {
        return is_resource($this->fileHandle);
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    /**
     * Open the log file for reading. Most other methods will open the file automatically if needed.
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function open(): self
    {
        if ($this->isOpen()) {
            return $this;
        }

        $this->fileHandle = fopen($this->file->path, 'r');

        if ($this->fileHandle === false) {
            throw new \Exception('Could not open "'.$this->file->path.'" for reading.');
        }

        $this->loadIndexFromCache();

        return $this;
    }

    /**
     * Close the file handle.
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function close(): self
    {
        if ($this->isClosed()) {
            return $this;
        }

        if ($this->indexChanged) {
            $this->writeIndexToCache();
        }

        if (fclose($this->fileHandle)) {
            $this->fileHandle = null;
            $this->nextLogIndex = 0;
        } else {
            throw new \Exception('Could not close the file "'.$this->file->path.'".');
        }

        return $this;
    }

    public function reverse(): self
    {
        $this->direction = self::DIRECTION_BACKWARD;

        return $this->reset();
    }

    /**
     * Skip a number of logs
     *
     * @param  int  $number
     * @return $this
     *
     * @throws \Exception
     */
    public function skip(int $number): self
    {
        if ($this->isClosed()) {
            $this->open();
        }

        $mergedIndex = $this->getMergedIndexForSelectedLevels();

        if (! empty($mergedIndex)) {
            if ($this->direction === self::DIRECTION_BACKWARD) {
                // Remember, we're going backwards from highest to lowest indices.
                foreach ($mergedIndex as $logIndex => $positionInFile) {
                    if ($logIndex >= $this->nextLogIndex) {
                        continue;
                    }
                    if ($number <= 0) {
                        break;
                    }

                    $this->nextLogIndex = $logIndex;
                    $number--;
                }
            } else {
                // The goal of this loop is to find the first index that matches the current log index
                foreach ($mergedIndex as $logIndex => $positionInFile) {
                    if ($logIndex <= $this->nextLogIndex) {
                        continue;
                    }
                    if ($number <= 0) {
                        break;
                    }

                    $this->nextLogIndex = $logIndex;
                    $number--;
                }
            }

            if ($number <= 0) {
                return $this;
            }

            // otherwise, if there's still a few items to skip (due to not all of them being indexed, for example),
            // then we will continue below by reading the new logs from the file until we skip the right number.
        }

        // not cached, thus we must read and discard each log.
        while ($number > 0) {
            $log = $this->next();

            if (is_null($log)) {
                break;
            }

            $number--;
        }

        return $this;
    }

    public function findPageForIndex(int $targetIndex, int $perPage = 25): int
    {
        $mergedIndex = $this->getMergedIndexForSelectedLevels();
        $currentPage = 1;
        $counter = 1;

        foreach ($mergedIndex as $index => $position) {
            if ($this->direction === self::DIRECTION_BACKWARD && $index <= $targetIndex) {
                break;
            } elseif ($this->direction === self::DIRECTION_FORWARD && $index >= $targetIndex) {
                break;
            }

            $counter++;

            if ($counter > $perPage) {
                $currentPage++;
                $counter = 1;
            }
        }

        return $currentPage;
    }

    public function onlyShow(int $targetIndex = 0): self
    {
        $this->onlyShowIndex = $targetIndex;

        return $this;
    }

    public function limit(int $number): self
    {
        $this->limit = $number;

        return $this;
    }

    public function search(string $query = null): self
    {
        $this->close();

        if (! empty($query) && Str::startsWith($query, 'log-index:')) {
            $this->query = null;
            $this->only(null);
            $this->onlyShow(intval(explode(':', $query)[1]));
        } elseif (! empty($query)) {
            $query = '/'.$query.'/i';

            if (! $this->isValidRegex($query)) {
                throw new InvalidRegularExpression();
            }

            $this->query = $query;
        } else {
            $this->query = null;
        }

        return $this;
    }

    protected function isValidRegex(string $regexString): bool
    {
        set_error_handler(function () {
        }, E_WARNING);
        $isValidRegex = preg_match($regexString, '') !== false;
        restore_error_handler();

        return $isValidRegex;
    }

    /**
     * This method scans the whole file quickly to index the logs in order to speed up
     * the retrieval of individual logs
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function scan(bool $force = false): self
    {
        if ($this->isClosed()) {
            $this->open();
        }

        if (! $this->requiresScan() && ! $force) {
            return $this;
        }

        // we don't care about the levels here, we should scan everything
        $levels = self::getDefaultLevels();
        $currentLog = '';
        $currentLogLevel = '';
        rewind($this->fileHandle);
        $currentLogPosition = ftell($this->fileHandle);

        while (($line = fgets($this->fileHandle)) !== false) {
            if (preg_match(self::LOG_MATCH_PATTERN, $line) === 1) {
                if ($currentLog !== '') {
                    if (is_null($this->query) || preg_match($this->query, $currentLog)) {
                        $this->indexLogPosition($this->nextLogIndex, $currentLogLevel, $currentLogPosition);
                    }

                    $this->nextLogIndex++;
                    $currentLog = '';
                }

                $currentLogPosition = ftell($this->fileHandle) - strlen($line);
                $lowercaseLine = strtolower($line);

                foreach ($levels as $level) {
                    if (strpos($lowercaseLine, '.'.$level) || strpos($lowercaseLine, $level.':')) {
                        $currentLogLevel = $level;
                        break;
                    }
                }
            }

            $currentLog .= $line;
        }

        if ($currentLog !== '') {
            if ((is_null($this->query) || preg_match($this->query, $currentLog))) {
                $this->indexLogPosition($this->nextLogIndex, $currentLogLevel, $currentLogPosition);
            }

            $this->nextLogIndex++;
            $currentLog = '';
        }

        $this->lastScanFileSize = ftell($this->fileHandle);
        $this->indexChanged = true;

        // Let's reset the position in preparation for real log reads.
        rewind($this->fileHandle);

        return $this->reset();
    }

    public function reset(): self
    {
        unset($this->_mergedIndex);
        $index = $this->getMergedIndexForSelectedLevels();

        if (empty($index)) {
            $index = [0];
        }

        if ($this->direction === self::DIRECTION_FORWARD) {
            $this->nextLogIndex = min(array_keys($index));
        } elseif ($this->direction === self::DIRECTION_BACKWARD) {
            $this->nextLogIndex = max(array_keys($index));
        }

        return $this;
    }

    /**
     * @return array|LevelCount[]
     *
     * @throws \Exception
     */
    public function getLevelCounts(): array
    {
        if (! $this->isOpen()) {
            $this->open();
        }

        $selectedLevels = $this->getSelectedLevels();
        $counts = [];

        foreach (self::getDefaultLevels() as $level) {
            $counts[$level] = new LevelCount(
                Level::from($level),
                count($this->logIndex[$level] ?? []),
                in_array($level, $selectedLevels)
            );
        }

        return $counts;
    }

    /**
     * @param  int|null  $limit
     * @return array|Log[]
     */
    public function get(int $limit = null)
    {
        if (! is_null($limit)) {
            $this->limit($limit);
        }

        $logs = [];

        while (($log = $this->next()) && (is_null($this->limit) || $this->limit > 0)) {
            $logs[] = $log;
            $this->limit--;
        }

        $this->limit = null;

        return $logs;
    }

    public function getLogAtIndex(int $index): ?Log
    {
        [$level, $text, $position] = $this->getLogTextAtIndex($index);

        // If we did not find any logs, this means either the file is empty, or
        // we have already reached the end of file. So we return early.
        if ($text === '') {
            return null;
        }

        $log = $this->makeLog($level, $text, $position);
        $log->index = $index;

        return $log;
    }

    public function next(): ?Log
    {
        $levels = $this->getSelectedLevels();

        [$level, $text, $position] = $this->getLogTextAtIndex($this->nextLogIndex);

        if (empty($text)) {
            return null;
        }

        $nextLog = $this->makeLog($level, $text, $position);

        $this->setNextLogIndex();

        return $nextLog;
    }

    public function getTotalItemCount(): int
    {
        return count($this->getMergedIndexForSelectedLevels());
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
            $this->getTotalItemCount(),
            $perPage,
            $page
        );
    }

    protected function makeLog(string $level, string $text, int $filePosition, $index = null)
    {
        return new Log($index ?? $this->nextLogIndex, $level, $text, $this->file->name, $filePosition);
    }

    /**
     * @param  int  $index
     * @return array|null Returns an array, [$level, $text, $position]
     *
     * @throws \Exception
     */
    protected function getLogTextAtIndex(int $index): ?array
    {
        if ($this->isClosed()) {
            $this->open();
        }

        $position = $this->getLogPositionFromIndex($index);

        if (is_null($position)) {
            return null;
        }

        fseek($this->fileHandle, $position, SEEK_SET);

        $currentLog = '';
        $currentLogLevel = '';

        while (($line = fgets($this->fileHandle)) !== false) {
            if (preg_match(self::LOG_MATCH_PATTERN, $line) === 1) {
                if ($currentLog !== '') {
                    // found the next log, so let's stop the loop and return the log we found
                    break;
                }

                $lowercaseLine = strtolower($line);
                foreach (self::getDefaultLevels() as $level) {
                    if (strpos($lowercaseLine, '.'.$level) || strpos($lowercaseLine, $level.':')) {
                        $currentLogLevel = $level;
                        break;
                    }
                }
            }

            $currentLog .= $line;
        }

        return [$currentLogLevel, $currentLog, $position];
    }

    protected function getLogPositionFromIndex(int $index): ?int
    {
        $fullIndex = $this->getMergedIndexForSelectedLevels();

        return $fullIndex[$index] ?? null;
    }

    protected function setNextLogIndex(): void
    {
        $numberSet = false;

        if ($this->direction === self::DIRECTION_FORWARD) {
            foreach ($this->getMergedIndexForSelectedLevels() as $logIndex => $logPosition) {
                if ($logIndex <= $this->nextLogIndex) {
                    continue;
                }

                $this->nextLogIndex = $logIndex;
                $numberSet = true;
                break;
            }

            if (! $numberSet) {
                $this->nextLogIndex++;
            }
        } else {
            foreach ($this->getMergedIndexForSelectedLevels() as $logIndex => $logPosition) {
                if ($logIndex >= $this->nextLogIndex) {
                    continue;
                }

                $this->nextLogIndex = $logIndex;
                $numberSet = true;
                break;
            }

            if (! $numberSet) {
                $this->nextLogIndex--;
            }
        }
    }

    protected function getMergedIndexForSelectedLevels(): array
    {
        if (! isset($this->_mergedIndex)) {
            $this->_mergedIndex = [];

            foreach ($this->getSelectedLevels() as $level) {
                if (! isset($this->logIndex[$level])) {
                    continue;
                }

                foreach ($this->logIndex[$level] as $logIndex => $logPosition) {
                    $this->_mergedIndex[$logIndex] = $logPosition;
                }
            }

            ksort($this->_mergedIndex);

            if ($this->direction === self::DIRECTION_BACKWARD) {
                $this->_mergedIndex = array_reverse($this->_mergedIndex, true);
            }
        }

        return $this->_mergedIndex ?? [];
    }

    protected function indexLogPosition(int $index, string $level, int $position): void
    {
        if (! isset($this->logIndex[$level])) {
            $this->logIndex[$level] = [];
        }

        $this->logIndex[$level][$index] = $position;
        $this->indexChanged = true;
    }

    protected function getIndexedLogPosition(int $index): ?int
    {
        foreach ($this->logIndex as $levelIndex) {
            if (isset($levelIndex[$index])) {
                return $levelIndex[$index];
            }
        }

        return null;
    }

    protected function writeIndexToCache(): void
    {
        $data = [$this->logIndex, $this->lastScanFileSize];

        $this->setRemoteCache($this->getIndexCacheKey(), $data, now()->addDay());
    }

    protected function loadIndexFromCache(): void
    {
        [$this->logIndex, $this->lastScanFileSize] = $this->getRemoteCache($this->getIndexCacheKey(), [[], 0]);

        if ($this->requiresScan()) {
            $this->scan();
        }
    }

    public function clearIndexCache(): void
    {
        $this->clearRemoteCache($this->getIndexCacheKey());
    }

    protected function requiresScan(): bool
    {
        return $this->lastScanFileSize !== $this->file->size();
    }

    public function __destruct()
    {
        $this->close();
    }
}
