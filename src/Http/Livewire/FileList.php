<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;

class FileList extends Component
{
    public ?string $selectedFileIdentifier = null;

    public function mount(string $selectedFileIdentifier = null)
    {
        $this->selectedFileIdentifier = $selectedFileIdentifier;

        if (! LogViewer::getFile($this->selectedFileIdentifier)) {
            $this->selectedFileIdentifier = null;
        }
    }

    public function render()
    {
        return view('log-viewer::livewire.file-list', [
            'files' => LogViewer::getFiles()->mapToGroups(function (LogFile $file) {
                if (config('log-viewer.group_by_subfolder')) {
                    $group = $file->subFolder ?: '_root';
                } else {
                    $group = '_root';
                }

                return [$group => $file];
            })->sortKeys(),
        ]);
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
    }
}
