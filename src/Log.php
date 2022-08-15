<?php

namespace Opcodes\LogViewer;

use Dotenv\Util\Regex;
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
        $this->text = mb_convert_encoding(explode("\n", $text, 2)[0], 'UTF-8', 'UTF-8');

        if (session()->get('log-viewer:shorter-stack-traces', false)) {
            $excludes = config('log-viewer.shorter_stack_trace_excludes', []);
            $emptyLineCharacter = '    ...';
            $lines = explode("\n", $text);
            $filteredLines = [];
            foreach ($lines as $line) {
                $shouldExclude = false;
                foreach ($excludes as $excludePattern) {
                    if (str_contains($line, $excludePattern)) {
                        $shouldExclude = true;
                        break;
                    }
                }

                if ($shouldExclude && end($filteredLines) !== $emptyLineCharacter) {
                    $filteredLines[] = $emptyLineCharacter;
                } elseif (!$shouldExclude) {
                    $filteredLines[] = $line;
                }
            }
            $text = implode("\n", $filteredLines);
        }

        $this->fullText = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
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
        return route('blv.index', ['file' => $this->fileName, 'query' => 'log-index:'.$this->index]);
    }
}
