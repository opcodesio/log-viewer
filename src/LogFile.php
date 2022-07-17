<?php

namespace Arukompas\BetterLogViewer;

use Illuminate\Support\Facades\Cache;

class LogFile
{
    public function __construct(
        public string $name,
        public string $path,
    ) {}

    public static function fromPath(string $filePath): LogFile
    {
        return new self(
            basename($filePath),
            $filePath,
        );
    }

    public function logs(): LogReader
    {
        return LogReader::instance($this);
    }

    public function size(): int
    {
        return filesize($this->path);
    }
}
