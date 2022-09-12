<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Str;

class LogFolder
{
    public string $identifier;

    protected mixed $files;

    public function __construct(
        public string $path,
        mixed $files,
    ) {
        $this->identifier = Str::substr(md5($path), -8, 8);
        $this->files = new LogFileCollection($files);
    }

    public function setFiles(array $files): self
    {
        $this->files = new LogFileCollection($files);

        return $this;
    }

    public function files(): LogFileCollection
    {
        return $this->files;
    }

    public function isRoot(): bool
    {
        return empty($this->path);
    }

    public function cleanPath(): string
    {
        $folder = $this->path;

        if (str_contains($folder, $storageLogsFolder = DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'logs')) {
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

        if (empty($folder)) {
            return [];
        }

        return explode(DIRECTORY_SEPARATOR, $folder);
    }

    public function pathFormatted(): string
    {
        $folder = $this->cleanPath();

        if (empty($folder)) {
            return $folder;
        }

        return str_replace(DIRECTORY_SEPARATOR, ' '.DIRECTORY_SEPARATOR.' ', $folder);
    }

    public function earliestTimestamp(): int
    {
        return $this->files()->min->earliestTimestamp();
    }

    public function latestTimestamp(): int
    {
        return $this->files()->max->latestTimestamp();
    }
}
