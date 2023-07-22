<?php

use Opcodes\LogViewer\LogFile;

it('can process new Horizon logs', function () {
    $file = generateLogFile('horizon_new_dummy.log', content: <<<EOF
Horizon started successfully.
  2023-07-22 16:13:33 App\Jobs\TestJob ............................... RUNNING
  2023-07-22 16:13:34 App\Jobs\TestJob ............................... 1s DONE
  2023-07-22 16:13:39 App\Jobs\TestJob ............................... RUNNING
  2023-07-22 16:13:39 App\Jobs\TestJob ........................... 3.67ms FAIL
EOF);
    $file = new LogFile($file->path);

    expect($file->type())->toBe('horizon'); // HorizonLog

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(4)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2023-07-22 16:13:33')
        ->and($logs[0]->level)->toBe('running')
        ->and($logs[0]->message)->toBe('App\Jobs\TestJob')
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2023-07-22 16:13:34')
        ->and($logs[1]->level)->toBe('done')
        ->and($logs[1]->message)->toBe('App\Jobs\TestJob')
        ->and($logs[1]->context['duration'])->toBe('1s');
});
