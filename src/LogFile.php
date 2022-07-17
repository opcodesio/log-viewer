<?php

namespace Arukompas\BetterLogViewer;

use Illuminate\Support\Facades\Cache;
use JetBrains\PhpStorm\ArrayShape;

class LogFile
{
    /**
     * Safety check to see whether we have already checked and loaded the cache.
     * We wouldn't want to override the existing cache with an empty index in case
     * we haven't read the whole file.
     *
     * @var bool
     */
    protected bool $cacheLoaded = false;

    /**
     * Contains an index of file positions where each log is located in.
     *
     * @var array
     */
    protected array $logIndex = [];

    /**
     * The log levels that should be read from this file.
     *
     * @var array|null
     */
    protected ?array $levels = null;

    protected ?int $limit = null;

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

    public function __construct(
        public string $name,
        public string $path,
        public int $size,
    ) {}

    public static function fromPath(string $filePath): LogFile
    {
        return new self(
            basename($filePath),
            $filePath,
            filesize($filePath)
        );
    }

    public function getLevels(): array
    {
        if (is_array($this->levels)) {
            return $this->levels;
        }

        return [
            'debug',
            'info',
            'notice',
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
            'processed',
            'failed',
        ];
    }

    public function isOpen(): bool
    {
        return is_resource($this->fileHandle);
    }

    public function isClosed(): bool
    {
        return !$this->isOpen();
    }

    /**
     * Open the log file for reading. Most other methods will open the file automatically if needed.
     *
     * @return $this
     * @throws \Exception
     */
    public function open(): self
    {
        if ($this->isOpen()) return $this;

        $this->fileHandle = fopen($this->path, 'r');

        if ($this->fileHandle === false) {
            throw new \Exception('Could not open "'.$this->path.'" for reading.');
        }

        $this->nextLogIndex = 0;

        if ($this->shouldUseCache()) {
            $this->loadIndexFromCache();
        }

        return $this;
    }

    /**
     * Close the file handle.
     *
     * @return $this
     * @throws \Exception
     */
    public function close(): self
    {
        if ($this->isClosed()) return $this;

        if (fclose($this->fileHandle)) {
            $this->fileHandle = null;
            $this->nextLogIndex = 0;
        } else {
            throw new \Exception('Could not close the file "'.$this->path.'".');
        }

        return $this;
    }

    /**
     * Skip a number of logs
     *
     * @param int $number
     * @return $this
     * @throws \Exception
     */
    public function skip(int $number): self
    {
        // IMPORTANT: this method is levels-aware:
        // If the user only wants to see "debug" and "info" levels, skipping 10 logs
        // will skip 10x "debug" or "info" logs, while ignoring all other levels.
        $levels = $this->getLevels();

        if ($this->isClosed()) $this->open();

        // There are 2 scenarios:
        // 1. We do know the position (from cache), and we can skip to it straight away
        //    by changing the file's seek position.
        // 2. We don't know the position of each log. Thus, we must read and discard the number
        //    of logs that we want to skip. The good thing is that reading those will cache
        //    the positions for faster processing later.

        $mergedIndex = [];

        foreach ($levels as $level) {
            if (!isset($this->logIndex[$level])) continue;

            foreach ($this->logIndex[$level] as $logIndex => $logPosition) {
                $mergedIndex[$logIndex] = $logPosition;
            }
        }

        ksort($mergedIndex);

        if (!empty($mergedIndex)) {
            // This file has an index, great! Although it could be incomplete in case of new logs
            // still being written constantly.

            ksort($mergedIndex);

            // The goal of this loop is to find the first index that matches the current log index
            foreach ($mergedIndex as $logIndex => $positionInFile) {
                if ($logIndex <= $this->nextLogIndex) continue;
                if ($number <= 0) break;

                $this->nextLogIndex = $logIndex;
                $number--;
            }

            // Let's fast-forward to whatever we have found.
            if (isset($mergedIndex[$this->nextLogIndex])) {
                fseek($this->fileHandle, $mergedIndex[$this->nextLogIndex], SEEK_SET);

                if ($number <= 0) return $this;
            }

            // otherwise, if there's still a few items to skip (due to not all of them being indexed, for example),
            // then we will continue below by reading the new logs from the file until we skip the right number.
        }

        // not cached, thus we must read and discard each log.
        while ($number > 0) {
            $log = $this->nextLog();

            if (is_null($log)) break;

            $number--;
        }

        return $this;
    }

    public function limit(int $number): self
    {
        $this->limit = $number;

        return $this;
    }

    /**
     * @param int|null $limit
     * @return array
     */
    public function getLogs(int $limit = null)
    {
        if (!is_null($limit)) {
            $this->limit($limit);
        }

        $logs = [];

        while (($log = $this->nextLog()) && (is_null($this->limit) || $this->limit > 0)) {
            $logs[] = $log;
            $this->limit--;
        }

        $this->limit = null;

        return $logs;
    }

    public function nextLog(): ?Log
    {
        if ($this->isClosed()) {
            $this->open();
        }

        $levels = $this->getLevels();
        $currentLog = '';
        $currentLogLevel = '';
        $currentLogPosition = ftell($this->fileHandle);

        while (($line = fgets($this->fileHandle)) !== false) {
            if (preg_match(Log::LOG_MATCH_PATTERN, $line) === 1) {

                if ($currentLog !== '') {
                    // found the next log, so let's seek the file handle back
                    // and stop the loop.
                    fseek($this->fileHandle, -strlen($line), SEEK_CUR);
                    break;
                }

                $lowercaseLine = strtolower($line);
                foreach ($levels as $level) {
                    if (strpos($lowercaseLine, '.' . $level) || strpos($lowercaseLine, $level . ':')) {
                        $currentLogLevel = $level;
                        break;
                    }
                }

                // Check the current position in file of this log, so we can fast-forward to it later.
                $currentLogPosition = ftell($this->fileHandle) - strlen($line);
            }

            $currentLog .= $line;
        }

        // If we did not find any logs, this means either the file is empty, or
        // we have already reached the end of file. So we return early.
        if ($currentLog === '') return null;

        $log = new Log($this->nextLogIndex, $currentLogLevel, $currentLog, $this->name, $currentLogPosition);

        $this->indexLogPosition($log->index, $log->level, $log->filePosition);

        $this->nextLogIndex++;

        if (!in_array($log->level, $levels)) {
            // the log we found was not the level we expected to receive.
            return $this->nextLog();
        }

        return $log;
    }

    public function indexLogPosition(int $index, string $level, int $position): void
    {
        if (!isset($this->logIndex[$level])) {
            $this->logIndex[$level] = [];
        }

        $this->logIndex[$level][$index] = $position;
    }

    public function getIndexedLogPosition(int $index): ?int
    {
        foreach ($this->logIndex as $levelIndex) {
            if (isset($levelIndex[$index])) return $levelIndex[$index];
        }

        return null;
    }

    public function shouldUseCache(): bool
    {
        return config('better-log-viewer.enable_cache', false);
    }

    public function getIndexCacheKey(): string
    {
        return 'better-log-viewer:'.$this->name.':logs_index';
    }

    public function getDefaultIndexCacheData(): array
    {
        return [
            'log_index' => [],
        ];
    }

    public function getIndexCacheData(): array
    {
        return [
            'log_index' => $this->logIndex,
        ];
    }

    public function setIndexCacheData(array $data = null): void
    {
        if (is_null($data)) {
            $data = $this->getDefaultIndexCacheData();
        }

        $this->logIndex = $data['log_index'] ?? [];
    }

    public function writeIndexToCache(): void
    {
        Cache::put(
            $this->getIndexCacheKey(),
            $this->getIndexCacheData(),
            now()->addMonth()
        );
    }

    public function loadIndexFromCache(): void
    {
        $this->setIndexCacheData(
            Cache::get($this->getIndexCacheKey(), null)
        );
        $this->cacheLoaded = true;
    }

    public function __destruct()
    {
        $this->close();

        if ($this->shouldUseCache() && $this->cacheLoaded) {
            $this->writeIndexToCache();
        }
    }
}
