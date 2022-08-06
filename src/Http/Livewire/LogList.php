<?php

namespace Arukompas\BetterLogViewer\Http\Livewire;

use Arukompas\BetterLogViewer\FileListReader;
use Arukompas\BetterLogViewer\LogFile;
use Arukompas\BetterLogViewer\LogReader;
use Livewire\Component;
use Livewire\WithPagination;

class LogList extends Component
{
    use WithPagination;

    public string $selectedFileName = '';
    public string $query = '';

    protected $queryString = [
        'query' => ['except' => ''],
    ];

    protected $listeners = [
        'fileSelected' => 'selectFile',
    ];

    public function render()
    {
        /** @var LogFile $file */
        $file = (new FileListReader())->getFiles()->firstWhere('name', $this->selectedFileName);

        $selectedLevels = $this->getSelectedLevels();

        $logQuery = $file?->logs()->only($selectedLevels)->reverse()->search($this->query);

        $levels = $logQuery?->getLevelCounts();
        $logs = $logQuery?->paginate(50);

        $memoryUsage = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB';
        $requestTime = number_format((microtime(true) - LARAVEL_START) * 1000, 0) . 'ms';

        return view('better-log-viewer::livewire.log-list', [
            'file' => $file,
            'levels' => $levels,
            'logs' => $logs,
            'memoryUsage' => $memoryUsage,
            'requestTime' => $requestTime,
        ]);
    }

    public function updatingQuery()
    {
        $this->resetPage();
    }

    public function clearQuery()
    {
        $this->query = '';
    }

    public function selectFile(string $fileName)
    {
        $this->resetPage();
        $this->selectedFileName = $fileName;
    }

    public function toggleLevel(string $level)
    {
        $this->resetPage();
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
