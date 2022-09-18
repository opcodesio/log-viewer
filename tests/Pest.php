<?php

use Illuminate\Support\Facades\File;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogIndex;
use Opcodes\LogViewer\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);
uses()
    ->afterEach(fn () => clearGeneratedLogFiles())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

/**
 * Generate log files with random data
 *
 * @param  array <int, string>  $files
 * @return void
 */
function generateLogFiles(array $files): void
{
    foreach ($files as $file) {
        $file = storage_path('logs/'.$file);

        if (File::exists($file)) {
            File::delete($file);
        }

        File::put($file, str()->random());

        test()->assertFileExists($file);
    }
}

function clearGeneratedLogFiles(): void
{
    File::cleanDirectory(storage_path('logs'));
}

function createLogIndex($file = null, $query = null, array $predefinedLogs = []): LogIndex
{
    if (is_null($file)) {
        $file = new LogFile('test.log', 'test.log');
    }

    $logIndex = new LogIndex($file, $query);

    foreach ($predefinedLogs as $predefinedLog) {
        $logIndex->addToIndex(...$predefinedLog);
    }

    return $logIndex;
}
