<?php

namespace Opcodes\LogViewer;

use Composer\InstalledVersions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class LogViewerService
{
    const DEFAULT_MAX_LOG_SIZE_TO_DISPLAY = 131_072;    // 128 KB

    protected ?Collection $_cachedFiles = null;

    protected mixed $authCallback;

    protected int $maxLogSizeToDisplay = self::DEFAULT_MAX_LOG_SIZE_TO_DISPLAY;

    protected function getFilePaths(): array
    {
        $files = [];

        foreach (config('log-viewer.include_files', []) as $pattern) {
            $files = array_merge($files, glob(Str::finish(storage_path('logs'), DIRECTORY_SEPARATOR).$pattern));
        }

        foreach (config('log-viewer.exclude_files', []) as $pattern) {
            $files = array_diff($files, glob(Str::finish(storage_path('logs'), DIRECTORY_SEPARATOR).$pattern));
        }

        $files = array_reverse($files);

        return array_filter($files, 'is_file');
    }

    /**
     * @return Collection|LogFile[]
     */
    public function getFiles(): Collection
    {
        if (! isset($this->_cachedFiles)) {
            $this->_cachedFiles = collect($this->getFilePaths())
                ->unique()
                ->map(fn ($file) => LogFile::fromPath($file))
                ->sortByDesc('path')
                ->values();
        }

        return $this->_cachedFiles;
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

    public function clearFileCache(): void
    {
        $this->_cachedFiles = null;
    }

    public function getRoutePrefix(): string
    {
        return config('log-viewer.route_path', 'log-viewer');
    }

    public function getRouteMiddleware(): array
    {
        return config('log-viewer.middleware', []) ?: ['web'];
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
        return InstalledVersions::getPrettyVersion('opcodesio/log-viewer');
    }
}
