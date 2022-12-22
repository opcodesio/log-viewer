<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        return empty($this->path)
            || $this->path === rtrim(LogViewer::basePathForLogs(), DIRECTORY_SEPARATOR);
    }

    public function cleanPath(): string
    {
        if ($this->isRoot()) {
            return 'root';
        }

        $folder = $this->path;

        $folder = str_replace(LogViewer::basePathForLogs(), 'root'.DIRECTORY_SEPARATOR, $folder);

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
        return $this->files()->min->earliestTimestamp() ?? 0;
    }

    public function latestTimestamp(): int
    {
        return $this->files()->max->latestTimestamp() ?? 0;
    }

    public function downloadFileName(): string
    {
        $cleanName = preg_replace('/[^A-Za-z0-9.]/', '_', $this->cleanPath());
        $cleanName = ltrim($cleanName, '_');

        return $cleanName.'.zip';
    }

    public function downloadUrl(): string
    {
        return route('blv.download-folder', $this->identifier);
    }

    public function download(): BinaryFileResponse
    {
        if (! extension_loaded('zip')) {
            throw new \Exception('This action requires PHP Zip extension.');
        }

        // zip it, and download it.
        $zipFileName = $this->downloadFileName();
        $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$zipFileName;

        // just in case we have created it before.
        @unlink($zipPath);

        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Could not open '.$zipPath.' for writing.');
        }

        /** @var LogFile $file */
        foreach ($this->files() as $file) {
            if (Gate::check('downloadLogFile', $file)) {
                $zip->addFromString(name: $file->name, content: LogViewer::getFilesystem($file->absolutePath)->get($file->path));
            }
        }

        try {
            $zip->close();
        } catch (\Exception $e) {
            throw new \Exception('Could not save Zip file: '.$e->getMessage(), $e->getCode(), $e);
        }

        return response()->download($zipPath, $zipFileName);
    }
}
