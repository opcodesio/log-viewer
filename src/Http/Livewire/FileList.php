<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogReader;

class FileList extends Component
{
    public ?string $selectedFileIdentifier = null;

    public bool $shouldLoadFiles = false;

    protected bool $cacheRecentlyCleared;

    protected $listeners = [
        'fullCacheCleared' => 'rescanAllFiles',
        'loadFiles' => 'loadFiles',
    ];

    public function mount(string $selectedFileIdentifier = null)
    {
        $this->selectedFileIdentifier = $selectedFileIdentifier;

        if (! LogViewer::getFile($this->selectedFileIdentifier)) {
            $this->selectedFileIdentifier = null;
        }
    }

    public function render()
    {
        $files = LogViewer::getFiles();

        $filesRequiringScans = $files->filter(fn (LogFile $file) => $file->logs()->requiresScan());
        $totalFileSize = $filesRequiringScans->sum->size();

        if ($filesRequiringScans->isEmpty() || $totalFileSize < (10 * 1024 * 1024)) {   // 10 MB
            $this->shouldLoadFiles = true;
        }

        if ($this->shouldLoadFiles) {
            $files = LogViewer::getFiles();

            foreach ($files as $file) {
                if ($file->logs()->requiresScan()) {
                    $file->logs()->scan();
                }

                // If there was a scan, it most likely loaded a big index array into memory,
                // so we should clear the instance before checking the next file
                // in order to save some memory.
                LogReader::clearInstance($file);
            }

            $files = $files->groupBy('subFolder')

                // sort by sub-folder name ASCENDING
                ->sortKeys()

                // Then individual log files by their latest timestamp DESCENDING
                ->map(fn (Collection $group) => $group->sortByDesc->latestTimestamp())

                // And then bring back into a flat view after everything's sorted
                ->flatten();
        }

        return view('log-viewer::livewire.file-list', [
            'files' => $this->shouldLoadFiles && $files ? $files : [],
            'totalFileSize' => $totalFileSize,
            'cacheRecentlyCleared' => $this->cacheRecentlyCleared ?? false,
        ]);
    }

    public function loadFiles()
    {
        $this->shouldLoadFiles = true;
    }

    public function rescanAllFiles()
    {
        $this->shouldLoadFiles = false;
        $this->emit('loadFiles');
    }

    public function selectFile(string $name)
    {
        $this->selectedFileIdentifier = $name;
    }

    public function deleteFile(string $fileIdentifier)
    {
        $file = LogViewer::getFile($fileIdentifier);

        if ($file) {
            Gate::authorize('deleteLogFile', $file);
            $file->delete();
        }

        if ($this->selectedFileIdentifier === $fileIdentifier) {
            $this->selectedFileIdentifier = null;
            $this->emit('fileSelected', $this->selectedFileIdentifier);
        }
    }

    public function clearCache(string $fileIdentifier)
    {
        LogViewer::getFile($fileIdentifier)?->clearCache();

        if ($this->selectedFileIdentifier === $fileIdentifier) {
            $this->emit('fileSelected', $this->selectedFileIdentifier);
        }

        $this->cacheRecentlyCleared = true;
    }
}
