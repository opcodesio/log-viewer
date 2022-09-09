<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LogFolder
{
    public string $identifier;

    public function __construct(
        public string $path,
        public Collection $files,
    ) {
        $this->identifier = Str::substr(md5($path), -8, 8);
    }

    public function isRoot(): bool
    {
        return empty($this->path);
    }

    public function cleanPath(): string
    {
        $folder = $this->path;

        if (str_contains($folder, $storageLogsFolder = DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs')) {
            // If we have /something/storage/logs, then we can remove it to make the string cleaner.
            // storage/logs is implied on Laravel environments.
            $folder = str_replace($storageLogsFolder, '', $folder);
        }

        if ($unixHomePath = getenv('HOME')) {
            $folder = str_replace($unixHomePath, '~', $folder);
        }

        return $folder;
    }

    public function pathParts(): array
    {
        $folder = $this->cleanPath();

        if (empty($folder)) return [];

        return explode(DIRECTORY_SEPARATOR, $folder);
    }

    public function pathFormatted(): string
    {
        $folder = $this->cleanPath();

        if (empty($folder)) return $folder;

        return str_replace(DIRECTORY_SEPARATOR, ' '.DIRECTORY_SEPARATOR.' ', $folder);
    }
}
