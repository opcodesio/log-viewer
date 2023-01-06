<?php

use Opcodes\LogViewer\LogFile;

test('log file can be instantiated with just a path to the file', function () {
    $path = storage_path('logs/laravel.log');
    file_put_contents($path, str_repeat('0', 10));  // 10 bytes

    $logFile = new LogFile($path);

    expect($logFile->path)->toBe($path)
        ->and($logFile->name)->toBe('laravel.log')
        ->and($logFile->size())->toBe(10);
});
