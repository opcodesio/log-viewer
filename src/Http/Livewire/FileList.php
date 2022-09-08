<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogReader;

class FileList extends Component
{
    const OLDEST_FIRST = 'asc';

    const NEWEST_FIRST = 'desc';

    const MIN_LOGS_FILE_SIZE_FOR_SCAN_STATE = 50 * 1024 * 1024; // 50 MB

    public ?string $selectedFileIdentifier = null;

    public bool $shouldLoadFilesImmediately = false;

    public string $direction = self::NEWEST_FIRST;

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
        $estimatedSecondsToScan = 0;

        if ($filesRequiringScans->isEmpty() || $totalFileSize < self::MIN_LOGS_FILE_SIZE_FOR_SCAN_STATE) {
            $this->shouldLoadFilesImmediately = true;
        }

        if ($this->shouldLoadFilesImmediately) {
            foreach ($filesRequiringScans as $file) {
                $file->logs()->scan();

                // If there was a scan, it most likely loaded a big index array into memory,
                // so we should clear the instance before checking the next file
                // in order to save some memory.
                LogReader::clearInstance($file);
            }

            $files = $files->groupBy('subFolder')

                ->when($this->direction === self::OLDEST_FIRST, function ($groupedFiles) {
                    return $groupedFiles->sortBy(function ($group) {
                        return $group->min->earliestTimestamp();
                    });
                }, function ($groupedFiles) {
                    return $groupedFiles->sortByDesc(function ($group) {
                        return $group->max->latestTimestamp();
                    });
                })

                // Then individual log files by their latest or earliest timestamps
                ->map(function (Collection $group) {
                    if ($this->direction === self::OLDEST_FIRST) {
                        return $group->sortBy->earliestTimestamp();
                    }

                    return $group->sortByDesc->latestTimestamp();
                })

                // And then bring back into a flat view after everything's sorted
                ->flatten();
        } else {
            // Otherwise, let's estimate the scan duration by sampling the speed of the first scan.
            // For more accurate results, let's scan a file that's more than 10 MB in size.
            $file = $filesRequiringScans->filter(fn ($file) => $file->sizeInMB() > 10)->first();

            if (is_null($file)) {
                $file = $filesRequiringScans->sortByDesc(fn ($file) => $file->size())->first();
            }

            $scanStart = microtime(true);
            $file->logs()->scan();
            $scanEnd = microtime(true);

            // because we already scanned it here, it won't need to be scanned later.
            $totalFileSize -= $file->size();

            $durationInMicroseconds = ($scanEnd - $scanStart) * 1000_000;
            $microsecondsPerMB = $durationInMicroseconds / $file->sizeInMB() * 1.10; // 10% buffer just in case
            $totalFileSizeInMB = $totalFileSize / 1024 / 1024;

            $estimatedSecondsToScan = ceil($totalFileSizeInMB * $microsecondsPerMB / 1000_000);
        }

        return view('log-viewer::livewire.file-list', [
            'files' => $this->shouldLoadFilesImmediately && $files ? $files : [],
            'totalFileSize' => $totalFileSize,
            'cacheRecentlyCleared' => $this->cacheRecentlyCleared ?? false,
            'estimatedTimeToScan' => CarbonInterval::seconds($estimatedSecondsToScan)->cascade()->forHumans(),
        ]);
    }

    public function loadFiles()
    {
        $this->shouldLoadFilesImmediately = true;
    }

    public function rescanAllFiles()
    {
        $this->shouldLoadFilesImmediately = false;
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
