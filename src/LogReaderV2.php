<?php

namespace Opcodes\LogViewer;

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Logs\BaseLog;

class LogReaderV2 extends LogReader
{
    protected LogIndexV2 $logIndexV2;

    protected ?int $currentLogIndex = null;

    protected ?int $skip = null;

    protected ?array $exceptLevels = null;

    protected function index(): LogIndex|LogIndexV2
    {
        return $this->file->index($this->query, LogIndexV2::class);
    }

    public function limit(int $number): static
    {
        $this->limit = $number;

        return $this;
    }

    public function skip(int $number): static
    {
        $this->index()->skip($number);
        $this->skip = $number;

        return $this;
    }

    /**
     * Load all log levels except the provided ones.
     *
     * @param  string|array|null  $levels
     */
    public function exceptLevels($levels = null): static
    {
        $this->index()->exceptLevels($levels);
        $this->exceptLevels = $this->index()->getExceptedLevels();

        return $this;
    }

    protected function getLogAtIndex(int $index): ?BaseLog
    {
        return $this->reset()->forward()->skip($index)->next();
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

    public function next(): ?BaseLog
    {
        if ($this->limit === 0) {
            return null;
        }

        if ($this->isClosed()) {
            $this->open();
        }

        // TODO: there's a bug, or big performance issue when reading backwards, or skipping certain levels.
        // If we're reading a level that only has very few entries, it still ends up scanning the whole file,
        // which can be very expensive.

        if (is_null($this->currentLogIndex)) {
            $groupToStartWith = $this->index()->nextGroup();

            if (empty($groupToStartWith)) {
                return null;
            } else {
                $this->skip -= $groupToStartWith['skipped_entries'];

                if ($this->direction === Direction::Forward) {
                    $this->currentLogIndex = $groupToStartWith['idx_from'];
                    fseek($this->fileHandle, $groupToStartWith['pos_from'], SEEK_SET);
                } else {
                    $this->currentLogIndex = $groupToStartWith['idx_to'];
                    fseek($this->fileHandle, $groupToStartWith['pos_to'], SEEK_SET);
                }
            }
        }

        $index = $this->currentLogIndex;

        list($currentLog, $filePosition, $currentLogLevel, $currentLogTimestamp) = match ($this->direction) {
            Direction::Forward => $this->readNextLineForward(),
            Direction::Backward => $this->readNextLogBackward(),
        };

        if (is_null($currentLog)) {
            return null;
        }

        if (isset($this->exceptLevels) && in_array($currentLogLevel, $this->exceptLevels)) {
            return $this->next();
        }

        if ($this->skip > 0) {
            $this->skip--;
            return $this->next();
        }

        if ($this->limit > 0) {
            $this->limit--;
        }

        return $this->makeLog($currentLog, $filePosition, $index);
    }

    protected function readNextLineForward(): array
    {
        $currentLog = '';
        $currentLogTimestamp = null;
        $currentLogLevel = null;
        $filePosition = ftell($this->fileHandle);

        // temp placeholders
        $ts = null;
        $lvl = null;

        while (($line = fgets($this->fileHandle)) !== false) {
            if ($this->logClass::matches($line, $ts, $lvl)) {
                if ($currentLog !== '') {
                    // found the next log, so let's roll back to previous position and return the log
                    if (isset($backToPos)) {
                        fseek($this->fileHandle, $backToPos, SEEK_SET);
                    }

                    break;
                }

                $currentLogTimestamp = $ts;
                $currentLogLevel = $lvl;
            }

            $currentLog .= $line;
            $backToPos = ftell($this->fileHandle);
        }

        if (empty($currentLog)) {
            return [null, null, null, null];
        }

        $this->currentLogIndex++;

        return [$currentLog, $filePosition, $currentLogLevel, $currentLogTimestamp];
    }

    protected function readNextLogBackward(): array
    {
        $currentLog = false;
        $currentLogTimestamp = null;
        $currentLogLevel = null;
        $filePosition = ftell($this->fileHandle);

        while (true) {
            if (ftell($this->fileHandle) === 0) {
                $filePosition = 0;
                break;
            }

            fseek($this->fileHandle, -1, SEEK_CUR);
            $char = fgetc($this->fileHandle);

            if ($char === "\n" && $currentLog === false) {
                // we have not yet started reading a line, so skip this character
                fseek($this->fileHandle, -1, SEEK_CUR);

                continue;
            }

            if ($char === "\n") {
                $filePosition = ftell($this->fileHandle);
                fseek($this->fileHandle, -1, SEEK_CUR);

                // check if the line matches the beginning of a log, and break
                if ($this->logClass::matches($currentLog, $currentLogTimestamp, $currentLogLevel)) {
                    break;
                }
            }

            $currentLog = $char.($currentLog ?: '');
            fseek($this->fileHandle, -1, SEEK_CUR);
        }

        if (empty($currentLog)) {
            return [null, null, null, null];
        }

        $this->currentLogIndex--;

        return [$currentLog, $filePosition, $currentLogLevel, $currentLogTimestamp];
    }

    public function reset(): static
    {
        $this->index()->reset();
        $this->currentLogIndex = null;

        return $this;
    }
}
