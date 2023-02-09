<?php

namespace Arukompas\BetterLogViewer\Http\Livewire;

use Arukompas\BetterLogViewer\FileListReader;
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
        return view('better-log-viewer::livewire.file-list', [
            'files' => $this->shouldLoadFiles ? (new FileListReader())->getFiles() : [],
        ]);
    }

    public function loadFiles()
    {
        $this->shouldLoadFiles = true;

        if (! empty($this->file)) {
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
        return FileListReader::findByName($fileName)?->download();
    }

    public function deleteFile(string $fileName)
    {
        FileListReader::findByName($fileName)?->delete();
    }
}
