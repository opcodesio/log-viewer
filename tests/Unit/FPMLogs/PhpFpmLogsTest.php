<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;

it('can process PHP FPM logs', function () {
    $file = generateLogFile('php-fpm.log', <<<'LOG'
[17-Jul-2021 17:07:10] NOTICE: fpm is running, pid 95716
[17-Jul-2021 17:07:10] NOTICE: ready to handle connections
[18-Jul-2021 11:10:47] NOTICE: fpm is running, pid 27178
[18-Jul-2021 11:10:47] NOTICE: ready to handle connections
LOG);

    $file = new LogFile($file->path);

    expect($file->type())->toBe(LogType::PHP_FPM);

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(4)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2021-07-17 17:07:10')
        ->and($logs[0]->level)->toBe('NOTICE')
        ->and($logs[0]->message)->toBe('fpm is running, pid 95716')
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2021-07-17 17:07:10')
        ->and($logs[1]->level)->toBe('NOTICE')
        ->and($logs[1]->message)->toBe('ready to handle connections');
});
