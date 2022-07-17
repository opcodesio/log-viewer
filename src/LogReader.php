<?php

namespace Arukompas\BetterLogViewer;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogReader
{
    /**
     * @var array
     */
    private $patterns = [
        'logs' => '/\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d{6}[\+-]\d\d:\d\d)?\].*/',
        'current_log' => [
            '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d{6}[\+-]\d\d:\d\d)?)\](?:.*?(\w+)\.|.*?)',
            ': (.*?)( in .*?:[0-9]+)?$/i'
        ],
        'files' => '/\{.*?\,.*?\}/i',
    ];

    /**
     * @var array
     */
    public $levelClasses = [
        'debug' => 'info',
        'info' => 'info',
        'notice' => 'info',
        'warning' => 'warning',
        'error' => 'danger',
        'critical' => 'danger',
        'alert' => 'danger',
        'emergency' => 'danger',
        'processed' => 'info',
        'failed' => 'warning',
    ];

    /**
     * @var array
     */
    private $levelIcons = [
        'debug' => 'info-circle',
        'info' => 'info-circle',
        'notice' => 'info-circle',
        'warning' => 'exclamation-triangle',
        'error' => 'exclamation-triangle',
        'critical' => 'exclamation-triangle',
        'alert' => 'exclamation-triangle',
        'emergency' => 'exclamation-triangle',
        'processed' => 'info-circle',
        'failed' => 'exclamation-triangle'
    ];

    public function getDefaultLevels()
    {
        return array_keys($this->levelClasses);
    }

    /**
     * @return Collection|LogFile[]
     */
    public function getFiles()
    {
        $files = [];

        foreach (config('better-log-viewer.include_files', []) as $pattern) {
            $files = array_merge($files, glob(storage_path() . '/logs/' . $pattern));
        }

        foreach (config('better-log-viewer.exclude_files', []) as $pattern) {
            $files = array_diff($files, glob(storage_path() . '/logs/' . $pattern));
        }

        $files = array_reverse($files);
        $files = array_filter($files, 'is_file');

        return collect($files ?? [])
            ->unique()
            ->map(fn ($file) => LogFile::fromPath($file))
            ->sortByDesc('name')
            ->values();
    }

    public function findFile($name)
    {
        return $this->getFiles()
            ->where('name', $name)
            ->first();
    }

    public function getLogsForFileOld(LogFile $file, $levels = null, &$count = null)
    {
        if (is_null($levels)) {
            $levels = array_keys($this->levelClasses);
        }

        $log = array();

        $file = app('files')->get($file->path);
        $headings = null;
        preg_match_all($this->patterns['logs'], $file, $headings);

        if (!is_array($headings)) {
            return $log;
        }

        $log_data = preg_split($this->patterns['logs'], $file);

        if ($log_data[0] < 1) {
            array_shift($log_data);
        }

        $timezone = config('app.timezone', 'UTC');

        foreach ($headings as $h) {
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach (array_keys($this->levelClasses) as $level) {
                    if (strpos(strtolower($h[$i]), '.' . $level) || strpos(strtolower($h[$i]), $level . ':')) {
                        $current = [];
                        preg_match($this->patterns['current_log'][0] . $level . $this->patterns['current_log'][1], $h[$i], $current);

                        if (!isset($current[4])) continue;

                        if (in_array($level, $levels)) {
                            $log[] = array(
                                'context' => $current[3],
                                'level' => $level,
                                'level_class' => $this->levelClasses[$level],
                                'level_img' => $this->levelIcons[$level],
                                'date' => Carbon::parse($current[1])->tz($timezone)->toDateTimeString(),
                                'text' => mb_convert_encoding($current[4], 'UTF-8', 'UTF-8'),
                                'in_file' => isset($current[5]) ? $current[5] : null,
                                'stack' => mb_convert_encoding(preg_replace("/^\n*/", '', $log_data[$i]), 'UTF-8', 'UTF-8')
                            );
                        }

                        if (!is_null($count) && isset($count[$level])) {
                            $count[$level]['count']++;
                        }
                    }
                }
            }
        }

        if (empty($log)) {
            $lines = explode(PHP_EOL, $file);
            $log = [];
            foreach ($lines as $key => $line) {
                $log[] = [
                    'context' => '',
                    'level' => '',
                    'level_class' => '',
                    'level_img' => '',
                    'date' => $key + 1,
                    'text' => $line,
                    'in_file' => null,
                    'stack' => '',
                ];
            }
        }

        return array_reverse($log);
    }
}
