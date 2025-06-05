<?php

namespace Opcodes\LogViewer\Logs;

use Carbon\Carbon;
use Opcodes\LogViewer\Facades\LogViewer;
use Str;

class LogSummary extends Log
{
    public static string $name = 'Log Summary';
    public static string $regex = '/^\[(?P<first_datetime>[^\]]+)\]\s*-\s*\[(?P<datetime>[^\]]+)\]\s+(?P<environment>\S+)\.(?P<level>\S+):\s+(?P<count>\d+)\s*\|\s*(?<message>.*)/x';
    public static array $columns = [
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'First', 'data_path' => 'extra.first_datetime'],
        ['label' => 'Last', 'data_path' => 'datetime'],
        ['label' => 'Env', 'data_path' => 'extra.environment'],
        ['label' => 'Count', 'data_path' => 'extra.count'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];
    public static string $regexFirstDatetimeKey = 'first_datetime';
    public static string $regexCountKey = 'count';
    public static string $regexEnvironmentKey = 'environment';

    public function fillMatches(array $matches = []): void
    {
        parent::fillMatches($matches);

        $this->message = $matches[static::$regexMessageKey] ?? '';
        $this->extra = [
            'environment' => $matches[static::$regexEnvironmentKey] ?? '',
            'count' => $matches[static::$regexCountKey] ?? 0,
            'first_datetime' => Carbon::parse($matches[static::$regexFirstDatetimeKey] ?? '')
                ->setTimezone(LogViewer::timezone())
                ->format("Y\u{2011}m\u{2011}d\u{00A0}H:i:s"),
        ];

        $raw = $this->text;

        if (preg_match('/\{".*}/s', $raw, $m)) {
            $jsonString = $m[0];
            $pos = Str::position($raw, '{"');
            if ($pos !== false) {
                $cleanText = substr($raw, 0, $pos);
                $this->text = $cleanText;
            }

            $escaped = $this->sanitizeJson($jsonString);

            $contextArray = json_decode($escaped, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $contextArray = [];
            }
        } else {
            $contextArray = [];
        }

        $this->context = $contextArray;
    }

    public function getOriginalText(): ?string
    {
        if (! $this->text) {
            return null;
        }

        $pos = Str::position($this->text, '| ');
        if ($pos === false) {
            return $this->text;
        }

        return Str::substr($this->text, $pos + 2);
    }

    protected function sanitizeJson(string $json): string
    {
        return Str::replace(
            ["\n", "\t"],
            ['\\n', '\\t'],
            $json
        );
    }
}
