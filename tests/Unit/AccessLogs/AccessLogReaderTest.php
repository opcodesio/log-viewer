<?php

use Opcodes\LogViewer\AccessLog;
use Opcodes\LogViewer\AccessLogReader;
use Opcodes\LogViewer\LogFile;

it('can read a specific file', function () {
    $accessLogs = file_get_contents(__DIR__.'/Fixtures/access_dummy.log');
    $file = generateLogFile('access.log', $accessLogs, type: LogFile::TYPE_HTTP_ACCESS);

    $accessLogReader = new AccessLogReader($file);

    $logs = $accessLogReader->get();

    expect($logs)->toBeArray()
        ->and($logs)->toHaveCount(10)
        ->and($logs[0])->toBeInstanceOf(AccessLog::class);
});
