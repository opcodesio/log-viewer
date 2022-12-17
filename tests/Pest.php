<?php

use Carbon\CarbonInterface;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogIndex;
use Opcodes\LogViewer\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);
uses()
    ->afterEach(function () {
        clearGeneratedLogFiles();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

/**
 * Generate log files with random data
 */
function generateLogFiles(array $files, string $content = null, bool $randomContent = false): array
{
    return array_map(
        fn ($file) => generateLogFile($file, $content, $randomContent),
        $files
    );
}

function generateLogFile(string $fileName = null, string $content = null, bool $randomContent = false): LogFile
{
    if (is_null($fileName)) {
        $fileName = \Illuminate\Support\Str::random().'.log';
    }

    $storage = LogViewer::getFilesystem();

    if ($storage->exists($fileName)) {
        $storage->delete($fileName);
    }

    $storage->put($fileName, $content ?? ($randomContent ? dummyLogData() : ''));

    // we perform a regular PHP assertion, so it doesn't count towards the unit test assertion count.
    assert($storage->exists($fileName));

    return new LogFile($fileName);
}

function dummyLogData(int $lines = null): string
{
    if (is_null($lines)) {
        $lines = rand(1, 10);
    }

    return implode("\n", array_map(
        fn ($_) => makeLogEntry(),
        range(1, $lines)
    ));
}

function clearGeneratedLogFiles(): void
{
    $storage = LogViewer::getFilesystem();
    $storage->delete($storage->allFiles());
}

function makeLogEntry(CarbonInterface $date = null, string $level = 'debug', string $message = 'Testing log entry'): string
{
    $dateFormatted = $date instanceof CarbonInterface ? $date->toDateTimeString() : now()->toDateTimeString();
    $level = strtoupper($level);

    return "[$dateFormatted] local.$level: $message";
}

function createLogIndex($file = null, $query = null, array $predefinedLogs = []): LogIndex
{
    if (is_null($file)) {
        $file = new LogFile('test.log');
    }

    $logIndex = new LogIndex($file, $query);

    foreach ($predefinedLogs as $predefinedLog) {
        $logIndex->addToIndex(...$predefinedLog);
    }

    $logIndex->save();

    return $logIndex;
}
