<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Utils\Utils;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogFolder
{
    public string $identifier;
    protected mixed $files;
    protected static string $rootPrefix;

    public function __construct(
        public string $path,
        mixed $files,
    ) {
        $this->identifier = Utils::shortMd5(Utils::getLocalIP().':'.$path);
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

    /**
     * Returns the prefix string used to represent the root folder.
     */
    public static function rootPrefix(): string
    {
        if (! isset(self::$rootPrefix)) {
            self::$rootPrefix = config('log-viewer.root_folder_prefix', 'root');
        }

        return self::$rootPrefix;
    }

    public function cleanPath(): string
    {
        if ($this->isRoot()) {
            return self::rootPrefix();
        }

        $folder = $this->path;

        $folder = str_replace(LogViewer::basePathForLogs(), self::rootPrefix().DIRECTORY_SEPARATOR, $folder);

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
        return route('log-viewer.folders.download', $this->identifier);
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

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Could not open '.$zipPath.' for writing.');
        }

        /** @var LogFile $file */
        foreach ($this->files() as $file) {
            if (Gate::check('downloadLogFile', $file)) {
                $zip->addFile($file->path, $file->name);
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
