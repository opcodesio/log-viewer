<?php

namespace Opcodes\LogViewer;

class HttpLogReader
{
    /**
     * Cached LogReader instances.
     */
    public static array $_instances = [];

    protected LogFile $file;

    protected ?int $limit = null;

    protected int $skip = 0;

    protected ?string $query = null;

    /**
     * @var resource|null
     */
    protected $fileHandle = null;

    protected string $direction = Direction::Forward;

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

    public static function clearInstances(): void
    {
        self::$_instances = [];
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

        $this->resetFilePointer();

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

    public function skip(int $number): self
    {
        $this->skip = $number;

        return $this;
    }

    public function limit(int $number): self
    {
        $this->limit = $number;

        return $this;
    }

    public function reverse(): self
    {
        $this->direction = Direction::Backward;

        return $this->reset();
    }

    public function reset(): self
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
     * @return array|Log[]
     */
    public function get(int $limit = null)
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
        list($text, $position) = match ($this->direction) {
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
}
