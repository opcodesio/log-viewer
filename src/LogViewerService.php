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
    public function getFiles()
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

    /**
     * This pattern, used for processing Laravel logs, returns these results:
     * $matches[0] - the full log line being tested.
     * $matches[1] - full timestamp between the square brackets (includes microseconds and timezone offset)
     * $matches[2] - timestamp microseconds, if available
     * $matches[3] - timestamp timezone offset, if available
     * $matches[4] - contents between timestamp and the severity level
     * $matches[5] - environment (local, production, etc)
     * $matches[6] - log severity (info, debug, error, etc)
     * $matches[7] - the log text, the rest of the text.
     */
    public function laravelRegexPattern(): string
    {
        return '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}\.?(\d{6}([\+-]\d\d:\d\d)?)?)\](.*?(\w+)\.|.*?)('
            .implode('|', array_filter(Level::caseValues()))
            .')?: (.*?)( in [\/].*?:[0-9]+)?$/is';
    }

    /**
     * Get the current version of the Log Viewer
     */
    public function version(): string
    {
        return InstalledVersions::getPrettyVersion('opcodesio/log-viewer');
    }
}
