<?php

namespace Opcodes\LogViewer;

class AccessLogReader
{
    /**
     * Cached LogReader instances.
     */
    public static array $_instances = [];

    protected LogFile $file;

    protected ?int $limit = null;

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

    /**
     * @return array|Log[]
     */
    public function get(int $limit = null)
    {
        // if (! is_null($limit)) {
        //     $this->limit($limit);
        // }

        $logs = [];

        while ($log = $this->next()) {
            $logs[] = $log;
        }

        return $logs;
    }

    public function next(): ?AccessLog
    {
        // We open it here to make we also check for possible need of index re-building.
        if ($this->isClosed()) {
            $this->open();
        }

        // get the next log line
        $line = fgets($this->fileHandle);

        if ($line === false) {
            return null;
        }

        $position = ftell($this->fileHandle);

        return $this->makeLog($line, $position);
    }

    protected function makeLog(string $text, int $filePosition): AccessLog
    {
        return AccessLog::fromString($text, $this->file->identifier, $filePosition);
    }

    public function __destruct()
    {
        $this->close();
    }
}
