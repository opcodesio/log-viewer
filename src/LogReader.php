<?php

namespace Opcodes\LogViewer;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;
use Opcodes\LogViewer\Facades\LogViewer;

class LogReader
{
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
     * Contains an index of file positions where each log entry is located in.
     */
    protected LogIndex $logIndex;

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

    public static function clearInstance(LogFile $file): void
    {
        if (isset(self::$_instances[$file->path])) {
            unset(self::$_instances[$file->path]);
        }
    }

    public function index(): LogIndex
    {
        if (! isset($this->logIndex)) {
            $this->logIndex = new LogIndex($this->file);
        }

        return $this->logIndex;
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

        $this->index()->forLevels($this->levels);

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

        $this->index()->forLevels($this->levels);

        unset($this->_mergedIndex);

        return $this;
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

        if ($this->requiresScan()) {
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
    public function close(): self
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

    public function reverse(): self
    {
        $this->direction = self::DIRECTION_BACKWARD;

        return $this->reset();
    }

    /**
     * Skip a number of logs
     *
     * @throws \Exception
     */
    public function skip(int $number): self
    {
        if ($this->isClosed()) {
            $this->open();
        }

        // TODO: skipping here could also skip some chunks from the LogIndex as well, saving some memory/CPU-time

        // TODO: perhaps skipping/limiting/moving across the index should all belong to the LogIndex class instead,
        // while the LogReader class would only be responsible for parsing logs at given positions.

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
        $this->index()->limit($number);

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

            $this->validateRegex($query);

            $this->query = $query;
        } else {
            $this->query = null;
        }

        unset($this->logIndex);

        return $this;
    }

    /**
     * @throws InvalidRegularExpression
     */
    protected function validateRegex(string $regexString): void
    {
        $error = null;
        set_error_handler(function (int $errno, string $errstr) use (&$error) {
            $error = $errstr;
        }, E_WARNING);
        preg_match($regexString, '');
        restore_error_handler();

        if (! empty($error)) {
            $error = str_replace('preg_match(): ', '', $error);
            throw new InvalidRegularExpression($error);
        }
    }

    /**
     * This method scans the whole file quickly to index the logs in order to speed up
     * the retrieval of individual logs
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

        if ($this->numberOfNewBytes() < 0) {
            // the file reduced in size... something must've gone wrong, so let's
            // force a full re-index.
            $force = true;
        }

        if ($force) {
            // when forcing, make sure we start from scratch and reset everything.
            $this->index()->clearCache();
        }

        // we don't care about the selected levels here, we should scan everything
        $logIndex = $this->index();
        $levels = self::getDefaultLevels();
        $logMatchPattern = LogViewer::logMatchPattern();
        $earliest_timestamp = $this->file->getMetaData('earliest_timestamp');
        $latest_timestamp = $this->file->getMetaData('latest_timestamp');
        $currentLog = '';
        $currentLogLevel = '';
        $currentTimestamp = null;
        fseek($this->fileHandle, $this->index()->getLastScannedFilePosition());
        $currentLogPosition = ftell($this->fileHandle);

        while (($line = fgets($this->fileHandle, 1024)) !== false) {
            /**
             * $matches[0] - the full line being checked
             * $matches[1] - the full timestamp in-between the square brackets, including the optional microseconds
             *               and the optional timezone offset
             * $matches[2] - the optional microseconds
             * $matches[3] - the optional timezone offset, like `+02:00` or `-05:30`
             */
            $matches = [];
            if (preg_match($logMatchPattern, $line, $matches) === 1) {
                if ($currentLog !== '') {
                    if (is_null($this->query) || preg_match($this->query, $currentLog)) {
                        $logIndex->addToIndex($currentLogPosition, $currentTimestamp, $currentLogLevel);
                    }

                    $currentLog = '';
                }

                $currentTimestamp = strtotime($matches[1] ?? '');
                $earliest_timestamp = min($earliest_timestamp ?? $currentTimestamp, $currentTimestamp);
                $latest_timestamp = max($latest_timestamp ?? $currentTimestamp, $currentTimestamp);
                $currentLogPosition = ftell($this->fileHandle) - strlen($line);
                $lowercaseLine = strtolower($line);

                foreach ($levels as $level) {
                    if (strpos($lowercaseLine, '.'.$level) || strpos($lowercaseLine, $level.':')) {
                        $currentLogLevel = $level;
                        break;
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

        if ($currentLog !== '' && preg_match($logMatchPattern, $currentLog) === 1) {
            if ((is_null($this->query) || preg_match($this->query, $currentLog))) {
                $logIndex->addToIndex($currentLogPosition, $currentTimestamp, $currentLogLevel);
            }

            $currentLog = '';
        }

        $logIndex->setLastScannedFilePosition(ftell($this->fileHandle));
        $logIndex->save();

        $this->file->setMetaData('name', $this->file->name);
        $this->file->setMetaData('path', $this->file->path);
        $this->file->setMetaData('size', $this->file->size());
        $this->file->setMetaData('earliest_timestamp', $this->index()->getEarliestTimestamp());
        $this->file->setMetaData('latest_timestamp', $this->index()->getLatestTimestamp());

        $this->file->saveMetaData();

        // Let's reset the position in preparation for real log reads.
        rewind($this->fileHandle);

        unset($this->_mergedIndex);

        return $this->reset();
    }

    public function reset(): self
    {
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

        return $this->index()->getLevelCounts()->map(function (int $count, string $level) use ($selectedLevels) {
            return new LevelCount(
                Level::from($level),
                $count,
                in_array($level, $selectedLevels)
            );
        })->toArray();
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

        $log = $this->makeLog($text, $position);
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

        $nextLog = $this->makeLog($text, $position);

        $this->setNextLogIndex();

        return $nextLog;
    }

    public function total(): int
    {
        return count($this->getMergedIndexForSelectedLevels());
    }

    /**
     * Alias for total()
     */
    public function count(): int
    {
        return $this->total();
    }

    /**
     * @deprecated Will be removed in v2.0.0
     */
    public function getTotalItemCount(): int
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

    protected function makeLog(string $text, int $filePosition, $index = null)
    {
        return new Log($index ?? $this->nextLogIndex, $text, $this->file->identifier, $filePosition);
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
            if (preg_match(LogViewer::logMatchPattern(), $line) === 1) {
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

    public function getMergedIndexForSelectedLevels(): array
    {
        if (! isset($this->_mergedIndex)) {
            $this->_mergedIndex = $this->index()
                ->forLevels($this->getSelectedLevels())
                ->getFlatArray();

            if ($this->direction === self::DIRECTION_BACKWARD) {
                $this->_mergedIndex = array_reverse($this->_mergedIndex, true);
            }
        }

        return $this->_mergedIndex ?? [];
    }

    public function numberOfNewBytes(): int
    {
        return $this->file->size() - $this->index()->getLastScannedFilePosition();
    }

    public function requiresScan(): bool
    {
        return $this->numberOfNewBytes() !== 0;
    }

    public function __destruct()
    {
        $this->close();
    }
}
