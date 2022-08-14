<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Livewire\Component;

class FileList extends Component
{
    public bool $shouldLoadFiles = false;
    public string $file = '';

    protected $queryString = [
        'file' => ['except' => ''],
    ];

    public function render()
    {
        return view('log-viewer::livewire.file-list', [
            'files' => $this->shouldLoadFiles ? LogViewer::getFiles() : [],
        ]);
    }

    public function loadFiles()
    {
        $this->shouldLoadFiles = true;

        if (!empty($this->file)) {
            $this->emit('fileSelected', $this->file);
        }
    }

    public function selectFile(string $fileName)
    {
        if ($fileName === $this->file) {
            $this->file = '';
        } else {
            $this->file = $fileName;
        }

        $this->emit('fileSelected', $this->file);
    }

    public function download(string $fileName)
    {
        return LogViewer::getFile($fileName)?->download();
    }

    public function deleteFile(string $fileName)
    {
        LogViewer::getFile($fileName)?->delete();
    }

    public function clearCache(string $fileName)
    {
        LogViewer::getFile($fileName)?->clearIndexCache();

        if ($this->file === $fileName) {
            $this->emit('fileSelected', $this->file);
        }
    }
}
