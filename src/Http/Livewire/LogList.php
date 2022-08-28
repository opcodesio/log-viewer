<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogReader;

class LogList extends Component
{
    use WithPagination;

    const OLDEST_FIRST = 'asc';

    const NEWEST_FIRST = 'desc';

    public ?string $selectedFileName = null;

    public string $query = '';

    public string $queryError = '';

    public int $perPage = 25;

    public string $direction = self::NEWEST_FIRST;

    public ?int $log = null;

    public bool $shorterStackTraces = false;

    public bool $refreshAutomatically = false;

    protected bool $cacheRecentlyCleared;

    protected $queryString = [
        'selectedFileName' => ['except' => null, 'as' => 'file'],
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
        $file = LogViewer::getFile($this->selectedFileName);
        $selectedLevels = $this->getSelectedLevels();
        $logQuery = $file?->logs()->only($selectedLevels);

        try {
            $logQuery?->search($this->query);
            if (Str::startsWith($this->query, 'log-index:')) {
                $logIndex = explode(':', $this->query)[1];
                $expandAutomatically = intval($logIndex) || $logIndex === '0';
            }
        } catch (InvalidRegularExpression $exception) {
            $this->queryError = $exception->getMessage();
        }

        if ($this->direction === self::NEWEST_FIRST) {
            $logQuery?->reverse();
        }

        $levels = $logQuery?->getLevelCounts();
        $logs = $logQuery?->paginate($this->perPage);
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : request()->server('REQUEST_TIME_FLOAT');

        $memoryUsage = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2).' MB';
        $requestTime = number_format((microtime(true) - $startTime) * 1000, 0).'ms';
        try {
            $version = json_decode(file_get_contents(__DIR__.'/../../../composer.json'))?->version ?? null;
        } catch (\Exception $e) {
            // Could not get the version from the composer file for some reason. Let's ignore that and move on.
            $version = null;
        }

        return view('log-viewer::livewire.log-list', [
            'file' => $file,
            'levels' => $levels,
            'logs' => $logs,
            'memoryUsage' => $memoryUsage,
            'requestTime' => $requestTime,
            'version' => $version,
            'expandAutomatically' => $expandAutomatically ?? false,
            'cacheRecentlyCleared' => $this->cacheRecentlyCleared ?? false,
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

    public function selectFile(?string $fileName)
    {
        if (isset($this->selectedFileName)) {
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

    public function reloadResults()
    {
        //
    }

    public function clearCacheAll()
    {
        LogViewer::getFiles()->each->clearCache();

        $this->cacheRecentlyCleared = true;
    }

    public function updatedPerPage($value)
    {
        $this->savePreferences();
    }

    public function updatedDirection($value)
    {
        $this->savePreferences();
    }

    public function toggleShorterStackTraces()
    {
        $this->shorterStackTraces = ! $this->shorterStackTraces;
        $this->savePreferences();
    }

    public function toggleAutomaticRefresh()
    {
        $this->refreshAutomatically = ! $this->refreshAutomatically;
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
        session()->put('log-viewer:log-list-preferences', [
            'per_page' => $this->perPage,
            'direction' => $this->direction,
            'shorter_stack_traces' => $this->shorterStackTraces,
            'refresh_automatically' => $this->refreshAutomatically,
        ]);
        session()->put('log-viewer:shorter-stack-traces', $this->shorterStackTraces);
    }

    public function loadPreferences(): void
    {
        $prefs = session()->get('log-viewer:log-list-preferences', []);

        $this->perPage = $prefs['per_page'] ?? $this->perPage;
        $this->direction = $prefs['direction'] ?? $this->direction;
        $this->shorterStackTraces = $prefs['shorter_stack_traces'] ?? $this->shorterStackTraces;
        $this->refreshAutomatically = $prefs['refresh_automatically'] ?? $this->refreshAutomatically;
    }
}
