<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogViewerService;

test('handles square brackets in the logs path', function ($folderPath) {
    // Get the original path inside which we'll create a dummy folder with square brackets
    $originalBasePath = LogViewer::basePathForLogs();
    $pathWithSquareBrackets = $originalBasePath.$folderPath.DIRECTORY_SEPARATOR;
    if (! file_exists($pathWithSquareBrackets)) {
        mkdir($pathWithSquareBrackets, recursive: true);
    }

    // Let's mock LogViewer to return the new path as the base path for logs
    app()->instance(
        LogViewerService::class,
        Mockery::mock(LogViewerService::class.'[basePathForLogs]')
            ->shouldReceive('basePathForLogs')->andReturn($pathWithSquareBrackets)->getMock()
    );
    LogViewer::clearResolvedInstance('log-viewer');

    // Create a dummy log file and make sure it's actually there
    $expectedLogFilePath = $pathWithSquareBrackets.($fileName = 'laravel.log');
    touch($expectedLogFilePath);
    expect(file_exists($expectedLogFilePath))->toBeTrue();

    // Act! Let's get the files and make sure they have found the log file created previously.
    $logFiles = LogViewer::getFiles();

    expect($logFiles)->toHaveCount(1)
        ->and($logFiles[0]->name)->toBe($fileName)
        ->and($logFiles[0]->path)->toBe($expectedLogFilePath);

    // clean up!
    unlink($expectedLogFilePath);
    rmdir($pathWithSquareBrackets);
})->with([
    '[logs]',
    '[logs',
    'logs]',
    '[[logs]]',
    '[logs][1]',
    'log[s]',
    'log[s',
    'log]s',
]);

test('can set an absolute path', function () {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('Absolute paths are currently not supported on Windows. If you want to help, please submit a PR.');
    }

    $first = generateLogFile('first.log');
    $second = generateLogFile('subfolder/second.log');

    config(['log-viewer.include_files' => [
        '*.log',    // equals to "storage/logs/*.log"
        dirname($second->path).'/*.log',
    ]]);

    $files = LogViewer::getFiles();

    expect($files)->toHaveCount(2)
        ->and($files->contains('path', $first->path))->toBeTrue()
        ->and($files->contains('path', $second->path))->toBeTrue();
});

test('can get deep nested logs', function () {
    $first = generateLogFile('first.log');
    $second = generateLogFile('subfolder/within/folder/second.log');

    config(['log-viewer.include_files' => [
        '*.log',    // equals to "storage/logs/*.log"
        '**/*.log',
    ]]);

    $files = LogViewer::getFiles();

    expect($files)->toHaveCount(2)
        ->and($files->contains('name', $first->name))->toBeTrue()
        ->and($files->contains('name', $second->name))->toBeTrue()
        ->and(file_exists($files[0]->path))->toBeTrue()
        ->and(file_exists($files[1]->path))->toBeTrue();
});

test('does not get nested logs with a single-asterisk wildcard', function () {
    $first = generateLogFile('first.log');
    $second = generateLogFile('subfolder/within/folder/second.log');

    config(['log-viewer.include_files' => [
        '*.log',    // equals to "storage/logs/*.log"
    ]]);

    $files = LogViewer::getFiles();

    expect($files)->toHaveCount(1)
        ->and($files->contains('name', $first->name))->toBeTrue()
        ->and($files->contains('name', $second->name))->toBeFalse()
        ->and(file_exists($files[0]->path))->toBeTrue();
});
