<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Livewire\Component;
use Opcodes\LogViewer\Facades\LogViewer;

class FileList extends Component
{
    public string $selectedFileName = '';

    public function mount(string $selectedFileName)
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
        LogViewer::getFile($fileName)?->delete();

        if ($this->selectedFileName === $fileName) {
            $this->selectedFileName = '';
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
