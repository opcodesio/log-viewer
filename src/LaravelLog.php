<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Utils\Utils;

class LaravelLog extends BaseLog
{
    public int $fullTextLength;

    public static array $columns = [
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Env', 'data_path' => 'context.environment'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    protected function parseText(): void
    {
        $this->text = mb_convert_encoding(rtrim($this->text, "\t\n\r"), 'UTF-8', 'UTF-8');
        $length = strlen($this->text);

        if ($length >= LogViewer::maxLogSize()) {
            $this->context['log_viewer']['log_size'] = $length;
            $this->context['log_viewer']['log_size_formatted'] = Utils::bytesForHumans($length);
        }

        [$firstLine, $theRestOfIt] = explode("\n", Str::finish($this->text, "\n"), 2);

        // sometimes, even the first line will have a HUGE exception with tons of debug data all in one line,
        // so in order to properly match, we must have a smaller first line...
        $firstLineSplit = str_split($firstLine, 1000);
        $matches = [];
        preg_match(LogViewer::laravelRegexPattern(), array_shift($firstLineSplit), $matches);

        $this->datetime = Carbon::parse($matches[1])->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );

        // $matches[2] contains microseconds, which is already handled
        // $matches[3] contains timezone offset, which is already handled

        $this->context['environment'] = $matches[5] ?? null;

        // There might be something in the middle between the timestamp
        // and the environment/level. Let's put that at the beginning of the first line.
        $middle = trim(rtrim($matches[4] ?? '', $this->context['environment'].'.'));

        $this->level = strtolower($matches[6] ?? '');

        $firstLineText = $matches[7];

        if (! empty($middle)) {
            $firstLineText = $middle.' '.$firstLineText;
        }

        $this->message = trim($firstLineText);
        $text = $firstLineText.($matches[8] ?? '').implode('', $firstLineSplit)."\n".$theRestOfIt;

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
            $this->context['log_viewer']['log_text_incomplete'] = true;
        } else {
            unset($this->context['log_viewer']);
        }

        $this->text = trim($text);

        $this->extractContextsFromFullText();

        unset($matches);
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        $matches = [];
        $result = preg_match(LogViewer::logMatchPattern(), $text, $matches) === 1;

        if ($result) {
            $timestamp = Carbon::parse($matches[1])?->timestamp;
            $level = strtolower($matches[6] ?? '');
        }

        return $result;
    }

    public function fullTextLengthFormatted(): string
    {
        return Utils::bytesForHumans($this->context['log_text_length']);
    }

    public function url(): string
    {
        return route('log-viewer.index', ['file' => $this->fileIdentifier, 'query' => 'log-index:'.$this->index]);
    }

    public function extractContextsFromFullText(): void
    {
        // The regex pattern to find JSON strings.
        $pattern = '/(\{(?:[^{}]|(?R))*\}|\[(?:[^\[\]]|(?R))*\])/';
        $contexts = [];

        // Find matches.
        preg_match_all($pattern, $this->text, $matches);

        if (! isset($matches[0])) {
            return;
        }

        // Loop through the matches.
        foreach ($matches[0] as $json_string) {
            // Try to decode the JSON string. If it fails, json_last_error() will return a non-zero value.
            $json_data = json_decode($json_string, true);

            if (json_last_error() == JSON_ERROR_CTRL_CHAR) {
                // might need to escape new lines
                $json_data = json_decode(str_replace("\n", '\\n', $json_string), true);
            }

            if (json_last_error() == JSON_ERROR_NONE) {
                $contexts[] = $json_data;
                $this->text = str_replace($json_string, '', $this->text);
            }
        }

        if (count($contexts) > 1) {
            $this->context['laravel_context'] = $contexts;
        } elseif (count($contexts) === 1) {
            $this->context['laravel_context'] = $contexts[0];
        }
    }
}
