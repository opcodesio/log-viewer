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

    public function sizeFormatted(): string
    {
        $size = $this->size();

        if ($size > ($gb = 1024 * 1024 * 1024)) {
            return number_format($size / $gb, 2) . ' GB';
        } elseif ($size > ($mb = 1024 * 1024)) {
            return number_format($size / $mb, 2) . ' MB';
        } elseif ($size > ($kb = 1024)) {
            return number_format($size / $kb, 2) . ' KB';
        }

        return $size . ' bytes';
    }
}
