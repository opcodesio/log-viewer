<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;

test('log file can be instantiated with just a path to the file', function () {
    $filename = 'laravel.log';
    LogViewer::getFilesystem()->put($filename, str_repeat('0', 10));

    $logFile = new LogFile($filename);

    expect($logFile->path)->toBe($filename)
        ->and($logFile->name)->toBe('laravel.log')
        ->and($logFile->size())->toBe(10);
});
