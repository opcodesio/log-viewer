<?php

namespace Opcodes\LogViewer\Http\Livewire;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Level;
use Opcodes\LogViewer\LogReader;
use Opcodes\LogViewer\PreferenceStore;

class LogList extends Component
{
    use WithPagination;

    const OLDEST_FIRST = 'asc';

    const NEWEST_FIRST = 'desc';

    public ?string $selectedFileIdentifier = null;

    public string $query = '';

    public string $queryError = '';

    public int $perPage = 25;

    public string $direction = self::NEWEST_FIRST;

    public ?int $log = null;

    public bool $shorterStackTraces = false;

    public bool $refreshAutomatically = false;

    protected bool $cacheRecentlyCleared;

    protected $queryString = [
        'selectedFileIdentifier' => ['except' => null, 'as' => 'file'],
        'query' => ['except' => ''],
        'log' => ['except' => ''],
    ];

    protected $listeners = [
        'fileSelected' => 'selectFile',
    ];

    public function mount()
    {
        $preferenceStore = app(PreferenceStore::class);

        $this->perPage = $preferenceStore->get('per_page', $this->perPage);
        $this->direction = $preferenceStore->get('log_sort_direction', $this->direction);
        $this->shorterStackTraces = $preferenceStore->get('shorter_stack_traces', $this->shorterStackTraces);
        $this->refreshAutomatically = $preferenceStore->get('refresh_automatically', $this->refreshAutomatically);

        $file = LogViewer::getFile($this->selectedFileIdentifier);

        $this->selectedFileIdentifier = $file?->identifier;
    }

    public function render()
    {
        $file = LogViewer::getFile($this->selectedFileIdentifier);
        $hasMoreResults = false;
        $percentScanned = 0;

        if (isset($file)) {
            $logQuery = $file->logs();
        } elseif (! empty($this->query)) {
            $logQuery = LogViewer::getFiles()->logs();
        }

        if (isset($logQuery)) {
            if ($this->page < 1) {
                $this->gotoPage(1);
            }

            try {
                $logQuery->search($this->query);

                if (isset($file) && Str::startsWith($this->query, 'log-index:')) {
                    $logIndex = explode(':', $this->query)[1];
                    $expandAutomatically = intval($logIndex) || $logIndex === '0';
                }

                if ($this->direction === self::NEWEST_FIRST) {
                    $logQuery->reverse();
                }

                $logQuery->scan(LogViewer::lazyScanChunkSize());

                $logQuery->setLevels($this->getSelectedLevels());

                $logs = $logQuery->paginate($this->perPage);
                $levels = $logQuery->getLevelCounts();

                if ($logs->lastPage() < $this->page) {
                    $this->gotoPage($logs->lastPage() ?? 1);

                    // re-create the paginator instance to fix a bug
                    $logs = $logQuery->paginate($this->perPage);
                }

                $hasMoreResults = $logQuery->requiresScan();
                $percentScanned = $logQuery->percentScanned();
            } catch (InvalidRegularExpression $exception) {
                $this->queryError = $exception->getMessage();
            }
        }

        return view('log-viewer::livewire.log-list', array_merge([
            'file' => $file,
            'levels' => $levels ?? [],
            'logs' => $logs ?? null,
            'expandAutomatically' => $expandAutomatically ?? false,
            'showLevelsDropdown' => isset($file) || ! empty($this->query),
            'cacheRecentlyCleared' => $this->cacheRecentlyCleared ?? false,
            'hasMoreResults' => $hasMoreResults,
            'percentScanned' => $percentScanned,
        ], $this->getRequestPerformanceInfo()));
    }

    public function submitSearch()
    {
        // We don't need to do anything extra. It's just to submit the new query parameter.
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

    public function selectFile(?string $fileIdentifier)
    {
        if (isset($this->selectedFileIdentifier)) {
            $this->resetPage();
        }

        $this->selectedFileIdentifier = $fileIdentifier;
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

    public function selectAllLevels()
    {
        $this->saveSelectedLevels(Level::caseValues());
    }

    public function deselectAllLevels()
    {
        $this->saveSelectedLevels([]);
    }

    public function reloadResults()
    {
        //
    }

    public function clearCacheAll()
    {
        LogViewer::getFiles()->each->clearCache();

        $this->query = '';
        $this->cacheRecentlyCleared = true;

        if (LogViewer::shouldEagerScanLogFiles()) {
            $this->dispatchBrowserEvent('scan-files');
        }
    }

    public function updatedPerPage($value)
    {
        app(PreferenceStore::class)->put('per_page', $value);
    }

    public function updatedDirection($value)
    {
        app(PreferenceStore::class)->put('log_sort_direction', $value);
    }

    public function toggleShorterStackTraces()
    {
        $this->shorterStackTraces = ! $this->shorterStackTraces;
        app(PreferenceStore::class)->put('shorter_stack_traces', $this->shorterStackTraces);
    }

    public function toggleAutomaticRefresh()
    {
        $this->refreshAutomatically = ! $this->refreshAutomatically;
        app(PreferenceStore::class)->put('refresh_automatically', $this->refreshAutomatically);
    }

    public function getSelectedLevels(): array
    {
        $levels = app(PreferenceStore::class)->get('selected_levels');

        if (is_null($levels)) {
            $levels = LogReader::getDefaultLevels();
        }

        return $levels;
    }

    public function saveSelectedLevels(array $levels): void
    {
        app(PreferenceStore::class)->put('selected_levels', $levels);
    }

    protected function getRequestPerformanceInfo(): array
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : request()->server('REQUEST_TIME_FLOAT');
        $memoryUsage = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2).' MB';
        $requestTime = number_format((microtime(true) - $startTime) * 1000, 0).'ms';

        return [
            'memoryUsage' => $memoryUsage,
            'requestTime' => $requestTime,
            'version' => LogViewer::version(),
        ];
    }
}
