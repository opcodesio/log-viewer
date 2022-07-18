<?php

namespace Arukompas\BetterLogViewer\Http\Livewire;

use Arukompas\BetterLogViewer\FileListReader;
use Livewire\Component;

class FileList extends Component
{
    public $shouldLoadFiles = false;

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
}
