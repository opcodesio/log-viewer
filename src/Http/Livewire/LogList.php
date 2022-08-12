<?php

namespace Arukompas\BetterLogViewer\Http\Livewire;

use Arukompas\BetterLogViewer\Exceptions\InvalidRegularExpression;
use Arukompas\BetterLogViewer\FileListReader;
use Arukompas\BetterLogViewer\LogFile;
use Arukompas\BetterLogViewer\LogReader;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class LogList extends Component
{
    use WithPagination;

    const OLDEST_FIRST = 'asc';
    const NEWEST_FIRST = 'desc';

    public string $selectedFileName = '';
    public string $query = '';
    public string $queryError = '';
    public int $perPage = 50;
    public string $direction = self::NEWEST_FIRST;
    public ?int $log = null;

    protected $queryString = [
        'query' => ['except' => ''],
        'log' => ['except' => ''],
    ];

    protected $listeners = [
        'fileSelected' => 'selectFile',
    ];

    public function mount()
    {
        $this->loadPreferences();
    }

    public function render()
    {
        /** @var LogFile $file */
        $file = FileListReader::findByName($this->selectedFileName);
        $selectedLevels = $this->getSelectedLevels();
        $logQuery = $file?->logs()->only($selectedLevels);

        try {
            $logQuery?->search($this->query);
            if (Str::startsWith($this->query, 'log-index:')) {
                $expandAutomatically = intval(explode(':', $this->query)[1]);
            }
        } catch (InvalidRegularExpression $exception) {
            $this->queryError = $exception->getMessage();
        }

        if ($this->direction === self::NEWEST_FIRST) {
            $logQuery?->reverse();
        }

        $levels = $logQuery?->getLevelCounts();
        $logs = $logQuery?->paginate($this->perPage);

        $memoryUsage = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB';
        $requestTime = number_format((microtime(true) - LARAVEL_START) * 1000, 0) . 'ms';

        return view('better-log-viewer::livewire.log-list', [
            'file' => $file,
            'levels' => $levels,
            'logs' => $logs,
            'memoryUsage' => $memoryUsage,
            'requestTime' => $requestTime,
            'expandAutomatically' => $expandAutomatically ?? false,
        ]);
    }

    public function updatingQuery()
    {
        $this->resetPage();
        $this->queryError = '';
    }

    public function clearQuery()
    {
        $this->query = '';
        $this->queryError = '';
    }

    public function selectFile(string $fileName)
    {
        if ($this->selectedFileName !== '') {
            $this->resetPage();
        }
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

    public function updatedPerPage($value)
    {
        $this->savePreferences();
    }

    public function updatedDirection($value)
    {
        $this->savePreferences();
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

    public function savePreferences(): void
    {
        session()->put('better-log-viewer:log-list-preferences', [
            'per_page' => $this->perPage,
            'direction' => $this->direction,
        ]);
    }

    public function loadPreferences(): void
    {
        $prefs = session()->get('better-log-viewer:log-list-preferences', []);

        $this->perPage = $prefs['per_page'] ?? $this->perPage;
        $this->direction = $prefs['direction'] ?? $this->direction;
    }
}
