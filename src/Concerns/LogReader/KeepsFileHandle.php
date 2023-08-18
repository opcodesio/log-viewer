<?php

namespace Opcodes\LogViewer\Concerns\LogReader;

use Opcodes\LogViewer\Exceptions\CannotCloseFileException;
use Opcodes\LogViewer\Exceptions\CannotOpenFileException;

trait KeepsFileHandle
{
    /** @var resource|null */
    protected $fileHandle = null;

    protected function isFileOpen(): bool
    {
        return is_resource($this->fileHandle);
    }

    protected function isFileClosed(): bool
    {
        return ! $this->isFileOpen();
    }

    /**
     * @throws CannotOpenFileException
     */
    protected function prepareFileForReading(): void
    {
        if ($this->isFileClosed()) {
            $this->openFile();
        }
    }

    /**
     * Open the log file for reading. Most other methods will open the file automatically if needed.
     *
     * @throws CannotOpenFileException
     */
    protected function openFile(): static
    {
        if ($this->isFileOpen()) {
            return $this;
        }

        try {
            $this->fileHandle = fopen($this->file->path, 'r');
        } catch (\ErrorException $exception) {
            throw new CannotOpenFileException('Could not open "'.$this->file->path.'" for reading.', 0, $exception);
        }

        if ($this->fileHandle === false) {
            throw new CannotOpenFileException('Could not open "'.$this->file->path.'" for reading.');
        }

        if (method_exists($this, 'onFileOpened')) {
            $this->onFileOpened();
        }

        return $this;
    }

    /**
     * Close the file handle.
     *
     * @throws CannotCloseFileException
     */
    protected function closeFile(): static
    {
        if ($this->isFileClosed()) {
            return $this;
        }

        if (fclose($this->fileHandle)) {
            $this->fileHandle = null;
        } else {
            throw new CannotCloseFileException('Could not close the file "'.$this->file->path.'".');
        }

        if (method_exists($this, 'onFileClosed')) {
            $this->onFileClosed();
        }

        return $this;
    }
}
