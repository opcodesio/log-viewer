<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogViewerService;

test('handles square brackets in the logs path', function () {
    // Get the original path inside which we'll create a dummy folder with square brackets
    $originalBasePath = LogViewer::basePathForLogs();
    $pathWithSquareBrackets = $originalBasePath . '[logs]' . DIRECTORY_SEPARATOR;
    if (! file_exists($pathWithSquareBrackets)) {
        mkdir($pathWithSquareBrackets, recursive: true);
    }

    // Let's mock LogViewer to return the new path as the base path for logs
    app()->instance(
        LogViewerService::class,
        Mockery::mock(LogViewerService::class . '[basePathForLogs]')
            ->shouldReceive('basePathForLogs')->andReturn($pathWithSquareBrackets)->getMock()
    );
    LogViewer::clearResolvedInstance('log-viewer');

    // Create a dummy log file and make sure it's actually there
    $expectedLogFilePath = $pathWithSquareBrackets . ($fileName = 'laravel.log');
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
});
