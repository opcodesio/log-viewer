<?php

namespace Arukompas\BetterLogViewer;

use Illuminate\Support\Carbon;

class Log
{
    const LOG_MATCH_PATTERN = '/\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d{6}[\+-]\d\d:\d\d)?\].*/';
    const LOG_CONTENT_PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(\.\d{6}[\+-]\d\d:\d\d)?)\](?:.*?(\w+)\.|.*?)';
    const LOG_CONTENT_PATTERN_2 = ': (.*?)( in .*?:[0-9]+)?$/is';

    public string $environment;
    public Carbon $time;

    public function __construct(
        public int $index,
        public string $level,
        public string $contents,
        public string $fileName,
        public int $filePosition,
    ) {
        $current = [];
        $pattern = self::LOG_CONTENT_PATTERN . $level . self::LOG_CONTENT_PATTERN_2;
        preg_match($pattern, $this->contents, $current);

        $this->environment = $current[3] ?? '';
        $this->time = Carbon::parse($current[1])->tz(config('app.timezone', 'UTC'));

        if (!empty($current[2])) {
            // we got microseconds!
            $this->time = $this->time->micros((int) $current[2]);
        }
    }
}
