<?php

use Illuminate\Support\Facades\File;
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
