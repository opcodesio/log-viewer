<?php

namespace Arukompas\BetterLogViewer;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Log
{
    const LOG_CONTENT_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d{6}[\+-]\d\d:\d\d)?)\](?:.*?(\w+)\.|.*?)';
    const LOG_CONTENT_PATTERN_2 = ': (.*?)( in .*?:[0-9]+)?$/is';

    public int $index;
    public Carbon $time;
    public Level $level;
    public string $environment;
    public string $text;
    public string $fullText;
    public string $fileName;
    public int $filePosition;

    public function __construct(
        int $index,
        string $level,
        string $text,
        string $fileName,
        int $filePosition,
    ) {
        $this->index = $index;
        $this->level = Level::from(strtolower($level));
        $this->fileName = $fileName;
        $this->filePosition = $filePosition;

        $matches = [];
        $pattern = self::LOG_CONTENT_PATTERN . $level . self::LOG_CONTENT_PATTERN_2;
        list($firstLine, $theRestOfIt) = explode("\n", $text, 2);
        preg_match($pattern, $firstLine, $matches);

        $this->environment = $matches[3] ?? '';
        $this->time = Carbon::parse($matches[1])->tz(config('app.timezone', 'UTC'));

        if (!empty($matches[2])) {
            // we got microseconds!
            $this->time = $this->time->micros((int) $matches[2]);
        }

        $text = $matches[4] . "\n" . $theRestOfIt;
        $this->text = mb_convert_encoding(explode("\n", $text)[0], 'UTF-8', 'UTF-8');
        $this->fullText = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // From the old implementation:

        // $log[] = array(
        //     'context' => $current[3],
        //     'level' => $level,
        //     'level_class' => $this->levelClasses[$level],
        //     'level_img' => $this->levelIcons[$level],
        //     'date' => Carbon::parse($current[1])->tz($timezone)->toDateTimeString(),
        //     'text' => mb_convert_encoding($current[4], 'UTF-8', 'UTF-8'),
        //     'in_file' => isset($current[5]) ? $current[5] : null,
        //     'stack' => mb_convert_encoding(preg_replace("/^\n*/", '', $log_data[$i]), 'UTF-8', 'UTF-8')
        // );
    }

    public function fullTextMatches(string $query = null): bool
    {
        if (empty($query)) return true;

        if (!Str::endsWith($query, '/i')) {
            $query = "/" . $query . "/i";
        }

        return (bool) preg_match($query, $this->fullText);
    }

    public function url(): string
    {
        return route('blv.index', ['file' => $this->fileName, 'log' => $this->index]);
    }
}
