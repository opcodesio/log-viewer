<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Opcodes\LogViewer\Facades\LogViewer;

class FileList extends Component
{
    public ?string $selectedFileName = null;

    public function mount(string $selectedFileName = null)
    {
        $this->selectedFileName = $selectedFileName;
    }

    public function render()
    {
        return view('log-viewer::livewire.file-list', [
            'files' => LogViewer::getFiles(),
        ]);
    }

    public function selectFile(string $name)
    {
        $this->selectedFileName = $name;
    }

    public function download(string $fileName)
    {
        return LogViewer::getFile($fileName)?->download();
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
        LogViewer::getFile($fileName)?->clearIndexCache();

        if ($this->selectedFileName === $fileName) {
            $this->emit('fileSelected', $this->selectedFileName);
        }
    }
}
