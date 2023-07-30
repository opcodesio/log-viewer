<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;

it('can process unknown logs', function () {
    $file = generateLogFile('default_dummy.log', content: <<<'EOF'
2022-10-07 09:41:00 [debug] Testing first message
2022-10-07 09:42:00 [info] Testing second message
EOF);

    expect($file->type()->value)->toBe(LogType::DEFAULT)
        ->and($logs = $file->logs()->get())->toHaveCount(2)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2022-10-07 09:41:00')
        ->and($logs[0]->level)->toBe('debug')
        ->and($logs[0]->message)->toBe('Testing first message')
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2022-10-07 09:42:00')
        ->and($logs[1]->level)->toBe('info')
        ->and($logs[1]->message)->toBe('Testing second message');
});
