<?php

namespace Opcodes\LogViewer;

use Composer\InstalledVersions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Glob;

class LogViewerService
{
    const DEFAULT_MAX_LOG_SIZE_TO_DISPLAY = 131_072;    // 128 KB

    protected ?Collection $_cachedFiles = null;

    protected mixed $authCallback;

    protected int $maxLogSizeToDisplay = self::DEFAULT_MAX_LOG_SIZE_TO_DISPLAY;

    protected function getFilePaths(): array
    {
        $files = [];

        // Because we use the Glob::toRegex function we have to escape the brackets
        $baseDir = str_replace(
            ['[', ']'],
            ['{LEFTBRACKET}', '{RIGHTBRACKET}'],
            str_replace('\\', '/', $this->basePathForLogs()),
        );
        $baseDir = str_replace(
            ['{LEFTBRACKET}', '{RIGHTBRACKET}', '\\'],
            ['[[]', '[]]', '\\\\'],
            $baseDir
        );

        foreach (config('log-viewer.include_files', []) as $pattern) {
            $absolute = true;
            if (! str_starts_with($pattern, DIRECTORY_SEPARATOR)) {
                $pattern = $baseDir.$pattern;
                $absolute = false;
            }

            $files = array_merge($files, $this->getFilePathsMatchingPattern($pattern, $absolute));
        }

        foreach (config('log-viewer.exclude_files', []) as $pattern) {
            $absolute = true;
            if (! str_starts_with($pattern, DIRECTORY_SEPARATOR)) {
                $pattern = $baseDir.$pattern;
                $absolute = false;
            }

            $files = array_diff($files, $this->getFilePathsMatchingPattern($pattern, $absolute));
        }

        return array_values(array_reverse($files));
    }

    protected function getFilePathsMatchingPattern($pattern, $absolute = false): array
    {
        $files = [];

        if (! $absolute) {
            $scannedFiles = $this->getFilesystem()->files($this->basePathForLogs());
        } else {
            $pathInfo = pathinfo($pattern);
            $dirname = $pathInfo['dirname'];
            $pattern = $pathInfo['basename'];

            $scannedFiles = $this->getFilesystem($dirname)->files();
        }

        foreach ($scannedFiles as $file) {
            $checkFile = str_replace('/', DIRECTORY_SEPARATOR, $file);
            if (preg_match(pattern: Glob::toRegex(glob: $pattern), subject: $file)) {
                $files[] = isset($dirname)
                    ? $dirname.DIRECTORY_SEPARATOR.$checkFile
                    : $checkFile;
            }
        }

        return $files;
    }

    public function basePathForLogs(): string
    {
        $rootFolder = Str::of(config('log-viewer.filesystem.root'));

        return ($rootFolder != '')
            ? $rootFolder->finish(DIRECTORY_SEPARATOR)
            : $rootFolder;
    }

    /**
     * @return LogFileCollection|LogFile[]
     */
    public function getFiles(): LogFileCollection
    {
        if (! isset($this->_cachedFiles)) {
            $this->_cachedFiles = (new LogFileCollection($this->getFilePaths()))
                ->unique()
                ->map(fn ($filePath) => new LogFile($filePath))
                ->values();
        }

        return $this->_cachedFiles;
    }

    public function getFilesGroupedByFolder(): LogFolderCollection
    {
        return LogFolderCollection::fromFiles($this->getFiles());
    }

    /**
     * Find the file with the given identifier or file name.
     *
     * @param  string|null  $fileIdentifier
     * @return LogFile|null
     */
    public function getFile(?string $fileIdentifier): ?LogFile
    {
        if (empty($fileIdentifier)) {
            return null;
        }

        $file = $this->getFiles()
            ->where('identifier', $fileIdentifier)
            ->first();

        if (! $file) {
            $file = $this->getFiles()
                ->where('name', $fileIdentifier)
                ->first();
        }

        return $file;
    }

    public function getFolder(?string $folderIdentifier): ?LogFolder
    {
        return $this->getFilesGroupedByFolder()
            ->first(function (LogFolder $folder) use ($folderIdentifier) {
                return (empty($folderIdentifier) && $folder->isRoot())
                    || $folder->identifier === $folderIdentifier
                    || $folder->path === $folderIdentifier;
            });
    }

    public function clearFileCache(): void
    {
        $this->_cachedFiles = null;
    }

    public function getRouteDomain(): ?string
    {
        return config('log-viewer.route_domain');
    }

    public function getRoutePrefix(): string
    {
        return config('log-viewer.route_path', 'log-viewer');
    }

    public function getRouteMiddleware(): array
    {
        return config('log-viewer.middleware', []) ?: ['web'];
    }

    public function getFilesystem($absolutePath = ''): Filesystem
    {
        if (! config('disable_absolute_filepaths') && ($absolutePath !== '') && is_dir($absolutePath)) {
            config()->set('filesystems.disks.log-viewer-absolute', [
                'driver' => 'local',
                'root' => $absolutePath,
            ]);

            return Storage::disk('log-viewer-absolute');
        }

        return Storage::disk(config('log-viewer.filesystem.disk'));
    }

    public function auth($callback = null): void
    {
        if (is_null($callback) && isset($this->authCallback)) {
            $canViewLogViewer = call_user_func($this->authCallback, request());

            if (! $canViewLogViewer) {
                throw new AuthorizationException('Unauthorized.');
            }
        } elseif (is_null($callback) && Gate::has('viewLogViewer')) {
            Gate::authorize('viewLogViewer');
        } elseif (! is_null($callback) && is_callable($callback)) {
            $this->authCallback = $callback;
        }
    }

    public function lazyScanChunkSize(): int
    {
        return intval(config('log-viewer.lazy_scan_chunk_size_in_mb', 100)) * 1024 * 1024;
    }

    /**
     * Get the maximum number of bytes of the log that we should display.
     *
     * @return int
     */
    public function maxLogSize(): int
    {
        return $this->maxLogSizeToDisplay;
    }

    public function setMaxLogSize(int $bytes): void
    {
        $this->maxLogSizeToDisplay = $bytes > 0 ? $bytes : self::DEFAULT_MAX_LOG_SIZE_TO_DISPLAY;
    }

    public function laravelRegexPattern(): string
    {
        return config('log-viewer.patterns.laravel.log_parsing_regex');
    }

    public function logMatchPattern(): string
    {
        return config('log-viewer.patterns.laravel.log_matching_regex');
    }

    /**
     * Get the current version of the Log Viewer
     */
    public function version(): string
    {
        if (app()->runningUnitTests()) {
            return 'unit-tests';
        }

        return InstalledVersions::getPrettyVersion('opcodesio/log-viewer') ?? 'dev-main';
    }
}
