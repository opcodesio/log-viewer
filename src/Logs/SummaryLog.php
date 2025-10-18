<?php

namespace Opcodes\LogViewer\Logs;

use Carbon\Carbon;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Logs\Log;
use Str;

class SummaryLog extends Log
{
    public static string $name = 'Summary';

    public static string $regex = '/^\{.*\}$/';

    public static array $seen = [];

    public static array $columns = [
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'First', 'data_path' => 'extra.first_datetime'],
        ['label' => 'Last', 'data_path' => 'datetime'],
        ['label' => 'Env', 'data_path' => 'extra.environment'],
        ['label' => 'Count', 'data_path' => 'extra.count'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];
    protected static string $regexContextKey = 'context';

    protected function parseText(array &$matches = []): void
    {
        $data = json_decode($this->text, true) ?: [];

        $matches[static::$regexDatetimeKey] = $data['last'] ?? '';
        $matches[static::$regexLevelKey] = $data['level'] ?? '';
        $matches[static::$regexMessageKey] = $data['message'] ?? '';
        $matches[static::$regexContextKey] = $data['context'] ?? [];

        $this->extra = [
            'first_datetime' => Carbon::parse($data['first'] ?? null)
                ->setTimezone(LogViewer::timezone())
                ->format("Y\u{2011}m\u{2011}d\u{00A0}H:i:s"),
            'environment' => $data['env'] ?? null,
            'count' => $data['count'] ?? null,
            'context' => $data['context'] ?? [],
        ];

        $this->text = $data['message'] ?? '';
    }

    protected function fillMatches(array $matches = []): void
    {
        parent::fillMatches($matches);

        $this->context = $matches[static::$regexContextKey];
    }
}
