<?php

namespace Arukompas\BetterLogViewer\Http\Livewire;

use Arukompas\BetterLogViewer\FileListReader;
use Arukompas\BetterLogViewer\LogFile;
use Livewire\Component;

class FileList extends Component
{
    public bool $shouldLoadFiles = false;
    public string $selectedFileName = '';

    public function render()
    {
        return view('better-log-viewer::livewire.file-list', [
            'files' => $this->shouldLoadFiles ? (new FileListReader())->getFiles() : [],
        ]);
    }

    public function loadFiles()
    {
        $this->shouldLoadFiles = true;
    }

    public function selectFile(string $fileName)
    {
        $this->selectedFileName = $fileName;
        $this->emit('fileSelected', $fileName);
    }
}
