<?php

namespace Arukompas\BetterLogViewer\Http\Livewire;

use Arukompas\BetterLogViewer\FileListReader;
use Arukompas\BetterLogViewer\LogFile;
use Arukompas\BetterLogViewer\LogReader;
use Livewire\Component;

class LogList extends Component
{
    public string $selectedFileName = '';

    protected $listeners = [
        'fileSelected' => 'selectFile',
    ];

    public function render()
    {
        /** @var LogFile $file */
        $file = (new FileListReader())->getFiles()->firstWhere('name', $this->selectedFileName);
        $selectedLevels = $this->getSelectedLevels();

        return view('better-log-viewer::livewire.log-list', [
            'file' => $file,
            'logs' => $file?->logs()->only($selectedLevels),
        ]);
    }

    public function selectFile(string $fileName)
    {
        $this->selectedFileName = $fileName;
    }

    public function toggleLevel(string $level)
    {
        $selectedLevels = $this->getSelectedLevels();

        if (in_array($level, $selectedLevels)) {
            $selectedLevels = array_diff($selectedLevels, [$level]);
        } else {
            $selectedLevels[] = $level;
        }

        $this->saveSelectedLevels($selectedLevels);
    }

    public function getSelectedLevels(): array
    {
        $levels = session()->get('selected_levels', []);

        if (empty($levels)) {
            $levels = LogReader::getDefaultLevels();
        }

        return $levels;
    }

    public function saveSelectedLevels(array $levels): void
    {
        session()->put('selected_levels', $levels);
    }
}
