<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;

it('can process Supervisor logs', function () {
    $file = generateLogFile('supervisor.log', <<<'LOG'
2022-12-27 16:17:43,990 CRIT Server 'unix_http_server' running without any HTTP authentication checking
2022-12-27 16:17:43,990 INFO supervisord started with pid 71047
2023-01-07 22:14:21,036 WARN received SIGTERM indicating exit request
2023-01-07 22:15:18,093 WARN No file matches via include "/opt/homebrew/etc/supervisor.d/*.ini"
LOG);

    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::SUPERVISOR);

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(4);
});
