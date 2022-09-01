<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;

class Log
{
    public int $index;

    public Carbon $time;

    public Level $level;

    public string $environment;

    public string $text;

    public string $fullText;

    public bool $fullTextIncomplete = false;

    public int $fullTextLength;

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
        $this->fullTextLength = strlen($text);

        $matches = [];
        $pattern = $this->getLogContentPattern($level);
        [$firstLine, $theRestOfIt] = explode("\n", $text, 2);

        // sometimes, even the first line will have a HUGE exception with tons of debug data all in one line,
        // so in order to properly match, we must have a smaller first line...
        $firstLineSplit = str_split($firstLine, 1000);
        preg_match($pattern, array_shift($firstLineSplit), $matches);

        $this->environment = $matches[3] ?? '';
        $this->time = Carbon::parse($matches[1])->tz(config('app.timezone', 'UTC'));

        if (! empty($matches[2])) {
            // we got microseconds!
            $this->time = $this->time->micros((int) $matches[2]);
        }

        $firstLineText = $matches[4];
        $this->text = mb_convert_encoding($firstLineText, 'UTF-8', 'UTF-8');
        $text = $firstLineText.($matches[5] ?? '').implode('', $firstLineSplit)."\n".$theRestOfIt;

        if (session()->get('log-viewer:shorter-stack-traces', false)) {
            $excludes = config('log-viewer.shorter_stack_trace_excludes', []);
            $emptyLineCharacter = '    ...';
            $lines = explode("\n", $text);
            $filteredLines = [];
            foreach ($lines as $line) {
                $shouldExclude = false;
                foreach ($excludes as $excludePattern) {
                    if (str_starts_with($line, '#') && str_contains($line, $excludePattern)) {
                        $shouldExclude = true;
                        break;
                    }
                }

                if ($shouldExclude && end($filteredLines) !== $emptyLineCharacter) {
                    $filteredLines[] = $emptyLineCharacter;
                } elseif (! $shouldExclude) {
                    $filteredLines[] = $line;
                }
            }
            $text = implode("\n", $filteredLines);
        }

        if (strlen($text) > LogViewer::maxLogSize()) {
            $text = Str::limit($text, LogViewer::maxLogSize());
            $this->fullTextIncomplete = true;
        }

        $this->fullText = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    protected function getLogContentPattern($level): string
    {
        $pattern = config('log-viewer.patterns.content');
        $pattern2 = config('log-viewer.patterns.content_2');

        return $pattern.$level.$pattern2;
    }

    public function fullTextMatches(string $query = null): bool
    {
        if (empty($query)) {
            return true;
        }

        if (! Str::endsWith($query, '/i')) {
            $query = '/'.$query.'/i';
        }

        return (bool) preg_match($query, $this->fullText);
    }

    public function fullTextLengthFormatted(): string
    {
        return bytes_formatted($this->fullTextLength);
    }

    public function url(): string
    {
        return route('blv.index', ['file' => $this->fileName, 'query' => 'log-index:'.$this->index]);
    }
}
