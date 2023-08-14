<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;

test('log file can be instantiated with just a path to the file', function () {
    $path = storage_path('logs/laravel.log');
    file_put_contents($path, str_repeat('0', 10));  // 10 bytes

    $logFile = new LogFile($path);

    expect($logFile->path)->toBe($path)
        ->and($logFile->name)->toBe('laravel.log')
        ->and($logFile->size())->toBe(10);
});

test('log file type can be unknown', function () {
    file_put_contents($path = storage_path('logs/unknown.log'), 'unknown log format');

    $logFile = new LogFile($path);

    expect($type = $logFile->type())->toBeInstanceOf(LogType::class)
        ->and($type->value)->toBe(LogType::DEFAULT)
        ->and($type->name())->toBe('Unknown');
});
