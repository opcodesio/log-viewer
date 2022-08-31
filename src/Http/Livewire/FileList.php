<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Opcodes\LogViewer\Facades\LogViewer;

class FileList extends Component
{
    public ?string $selectedFileName = null;

    public bool $shouldLoadFiles = false;

    public function mount(string $selectedFileName = null)
    {
        $this->selectedFileName = $selectedFileName;

        if (! LogViewer::getFile($this->selectedFileName)) {
            $this->selectedFileName = null;
        }
    }

    public function render()
    {
        if ($this->shouldLoadFiles) {
            $files = LogViewer::getFiles();

            foreach ($files as $file) {
                $file->logs()->scan();
            }

            $files = $files->sortByDesc->latestTimestamp();
        }

        return view('log-viewer::livewire.file-list', [
            'files' => $files ?? [],
        ]);
    }

    public function loadFiles()
    {
        $this->shouldLoadFiles = true;
    }

    public function selectFile(string $name)
    {
        $this->selectedFileName = $name;
    }

    public function deleteFile(string $fileName)
    {
        $file = LogViewer::getFile($fileName);

        if ($file) {
            Gate::authorize('deleteLogFile', $file);
            $file->delete();
        }

        if ($this->selectedFileName === $fileName) {
            $this->selectedFileName = null;
            $this->emit('fileSelected', $this->selectedFileName);
        }
    }

    public function clearCache(string $fileName)
    {
        LogViewer::getFile($fileName)?->clearCache();

        if ($this->selectedFileName === $fileName) {
            $this->emit('fileSelected', $this->selectedFileName);
        }
    }
}
