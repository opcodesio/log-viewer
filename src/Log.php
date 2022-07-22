<?php

namespace Arukompas\BetterLogViewer;

use Illuminate\Support\Carbon;

class Log
{
    const LOG_CONTENT_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d{6}[\+-]\d\d:\d\d)?)\](?:.*?(\w+)\.|.*?)';
    const LOG_CONTENT_PATTERN_2 = ': (.*?)( in .*?:[0-9]+)?$/is';

    public int $index;
    public Carbon $time;
    public Level $level;
    public string $environment;
    public string $text;
    public string $stack;
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

        $current = [];
        $pattern = self::LOG_CONTENT_PATTERN . $level . self::LOG_CONTENT_PATTERN_2;
        preg_match($pattern, $text, $current);

        $this->environment = $current[3] ?? '';
        $this->time = Carbon::parse($current[1])->tz(config('app.timezone', 'UTC'));

        if (!empty($current[2])) {
            // we got microseconds!
            $this->time = $this->time->micros((int) $current[2]);
        }

        $text = $current[4];
        $this->text = mb_convert_encoding(explode("\n", $text)[0], 'UTF-8', 'UTF-8');
        $this->stack = mb_convert_encoding(str_replace($this->text, '', $text), 'UTF-8', 'UTF-8');

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
}
