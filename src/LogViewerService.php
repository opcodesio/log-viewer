<?php

namespace Opcodes\LogViewer;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class LogViewerService
{
    protected ?Collection $_cachedFiles = null;

    protected mixed $authCallback;

    /**
     * @return Collection|LogFile[]
     */
    public function getFiles()
    {
        if (! isset($this->_cachedFiles)) {
            $files = [];

            foreach (config('log-viewer.include_files', []) as $pattern) {
                $files = array_merge($files, glob(storage_path().'/logs/'.$pattern));
            }

            foreach (config('log-viewer.exclude_files', []) as $pattern) {
                $files = array_diff($files, glob(storage_path().'/logs/'.$pattern));
            }

            $files = array_reverse($files);
            $files = array_filter($files, 'is_file');

            $this->_cachedFiles = collect($files ?? [])
                ->unique()
                ->map(fn ($file) => LogFile::fromPath($file))
                ->sortByDesc('name')
                ->values();
        }

        return $this->_cachedFiles;
    }

    public function getFile(?string $fileName): ?LogFile
    {
        if (empty($fileName)) {
            return null;
        }

        return $this->getFiles()
            ->where('name', $fileName)
            ->first();
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
}
