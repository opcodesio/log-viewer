<?php

namespace Arukompas\BetterLogViewer\Http\Livewire;

use Arukompas\BetterLogViewer\FileListReader;
use Livewire\Component;

class LogList extends Component
{
    public string $selectedFileName = '';

    protected $listeners = [
        'fileSelected' => 'selectFile',
    ];

    public function render()
    {
        return view('better-log-viewer::livewire.log-list', [
            'file' => (new FileListReader())->getFiles()->firstWhere('name', $this->selectedFileName),
        ]);
    }

    public function selectFile(string $fileName)
    {
        $this->selectedFileName = $fileName;
    }
}
