<?php

namespace Opcodes\LogViewer\Readers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Opcodes\LogViewer\Concerns;
use Opcodes\LogViewer\Exceptions\CannotOpenFileException;
use Opcodes\LogViewer\Exceptions\SkipLineException;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LevelCount;
use Opcodes\LogViewer\LogIndex;
use Opcodes\LogViewer\Logs\Log;

class IndexedLogReader extends BaseLogReader implements LogReaderInterface
{
    use Concerns\LogReader\CanFilterUsingIndex;
    use Concerns\LogReader\CanSetDirectionUsingIndex;

    protected LogIndex $logIndex;
    protected bool $lazyScanning = false;
    protected int $mtimeBeforeScan;

    protected function onFileOpened(): void
    {
        if ($this->requiresScan() && ! $this->lazyScanning) {
            $this->scan();
        } else {
            $this->reset();
        }
    }

    protected function index(): LogIndex
    {
        return $this->file->index($this->query);
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
     * @throws CannotOpenFileException
     */
    public function scan(?int $maxBytesToScan = null, bool $force = false): static
    {
        if (is_null($maxBytesToScan)) {
            $maxBytesToScan = LogViewer::lazyScanChunkSize();
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

        $this->prepareFileForReading();

        $stopScanningAfter = microtime(true) + LogViewer::lazyScanTimeout();
        $this->mtimeBeforeScan = $this->file->mtime();

        // we don't care about the selected levels here, we should scan everything
        $logIndex = $this->index();
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
            && ($line = fgets($this->fileHandle)) !== false
        ) {
            $matches = [];
            $ts = null;
            $lvl = null;

            try {
                // first, let's see if it matches the new log entry. Does not take search query into account yet.
                $lineMatches = $this->logClass::matches(trim($line), $ts, $lvl);
            } catch (SkipLineException $exception) {
                continue;
            }

            if ($lineMatches) {
                if ($currentLog !== '') {
                    // Now, let's see if it matches the search query if set.
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
        $this->prepareFileForReading();
        $levelClass = $this->logClass::levelClass();

        return $this->index()->getLevelCounts()->map(function (int $count, string $level) {
            return new LevelCount(
                $this->levelClass::from($level),
                $count,
                $this->index()->isLevelSelected($level),
            );
        })->sortBy(fn (LevelCount $levelCount) => $levelCount->level->getName(), SORT_NATURAL)->toArray();
    }

    /**
     * @return array|Log[]
     *
     * @throws CannotOpenFileException
     */
    public function get(?int $limit = null): array
    {
        if (! is_null($limit) && method_exists($this, 'limit')) {
            $this->limit($limit);
        }

        $logs = [];

        while ($log = $this->next()) {
            $logs[] = $log;
        }

        return $logs;
    }

    /**
     * @throws CannotOpenFileException
     */
    public function next(): ?Log
    {
        $this->prepareFileForReading();

        [$index, $position] = $this->index()->next();

        if (is_null($index)) {
            return null;
        }

        $text = $this->getLogTextAtPosition($position);

        if (empty($text)) {
            return null;
        }

        return $this->makeLog($text, $position, $index);
    }

    public function total(): int
    {
        return $this->index()->count();
    }

    public function paginate(int $perPage = 25, ?int $page = null)
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

    protected function getLogAtIndex(int $index): ?Log
    {
        $position = $this->index()->getPositionForIndex($index);

        $text = $this->getLogTextAtPosition($position);

        // If we did not find any logs, this means either the file is empty, or
        // we have already reached the end of file. So we return early.
        if ($text === '') {
            return null;
        }

        return $this->makeLog($text, $position, $index);
    }

    /**
     * Returns the full log text found start at the given position.
     *
     * @throws CannotOpenFileException
     */
    protected function getLogTextAtPosition(int $position): ?string
    {
        $this->prepareFileForReading();

        fseek($this->fileHandle, $position, SEEK_SET);

        $currentLog = '';

        while (($line = fgets($this->fileHandle)) !== false) {
            if ($this->logClass::matches($line)) {
                if ($currentLog !== '') {
                    // found the next log, so let's stop the loop and return the log we found
                    break;
                }
            } elseif ($currentLog === '') {
                continue;
            }

            $currentLog .= $line;
        }

        return $currentLog;
    }
}
