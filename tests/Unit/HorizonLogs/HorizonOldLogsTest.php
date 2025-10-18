<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;

it('can process old Horizon logs', function () {
    $file = generateLogFile('horizon_old_dummy.log', content: <<<EOF
[2022-10-07 09:41:00][13acffa6-fd25-4d36-876a-b48e968302a4] Processing: App\Notifications\BookingUpdated
[2022-10-07 09:41:00][13acffa6-fd25-4d36-876a-b48e968302a4] Processed:  App\Notifications\BookingUpdated
EOF);
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::HORIZON_OLD); // HorizonOldLog

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(2)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2022-10-07 09:41:00')
        ->and($logs[0]->level)->toBe('Processing')
        ->and($logs[0]->message)->toBe('App\Notifications\BookingUpdated')
        ->and($logs[0]->context['uuid'])->toBe('13acffa6-fd25-4d36-876a-b48e968302a4')
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2022-10-07 09:41:00')
        ->and($logs[1]->level)->toBe('Processed')
        ->and($logs[1]->message)->toBe('App\Notifications\BookingUpdated')
        ->and($logs[1]->context['uuid'])->toBe('13acffa6-fd25-4d36-876a-b48e968302a4');
});

it('can process old Horizon logs with failed status', function () {
    $file = generateLogFile('horizon_old_failed.log', content: <<<EOF
[2022-10-07 09:41:00][a1b2c3d4-e5f6-7890-1234-567890abcdef] Processing: App\Jobs\SendEmailJob
[2022-10-07 09:41:01][a1b2c3d4-e5f6-7890-1234-567890abcdef] Failed:     App\Jobs\SendEmailJob
[2022-10-07 09:42:00][b2c3d4e5-f6a7-8901-2345-67890abcdef1] Processing: App\Events\UserRegistered
[2022-10-07 09:42:00][b2c3d4e5-f6a7-8901-2345-67890abcdef1] Processed:  App\Events\UserRegistered
EOF);
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::HORIZON_OLD); // HorizonOldLog

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(4)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2022-10-07 09:41:00')
        ->and($logs[0]->level)->toBe('Processing')
        ->and($logs[0]->message)->toBe('App\Jobs\SendEmailJob')
        ->and($logs[0]->context['uuid'])->toBe('a1b2c3d4-e5f6-7890-1234-567890abcdef')
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2022-10-07 09:41:01')
        ->and($logs[1]->level)->toBe('Failed')
        ->and($logs[1]->message)->toBe('App\Jobs\SendEmailJob')
        ->and($logs[1]->context['uuid'])->toBe('a1b2c3d4-e5f6-7890-1234-567890abcdef')
        ->and($logs[2]->datetime->toDateTimeString())->toBe('2022-10-07 09:42:00')
        ->and($logs[2]->level)->toBe('Processing')
        ->and($logs[2]->message)->toBe('App\Events\UserRegistered')
        ->and($logs[3]->datetime->toDateTimeString())->toBe('2022-10-07 09:42:00')
        ->and($logs[3]->level)->toBe('Processed')
        ->and($logs[3]->message)->toBe('App\Events\UserRegistered');
});
