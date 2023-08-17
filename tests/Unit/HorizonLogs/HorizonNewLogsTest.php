<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogLevels\HorizonStatusLevel;
use Opcodes\LogViewer\Logs\LogType;

it('can process new Horizon logs', function () {
    $file = generateLogFile(content: <<<EOF
Horizon started successfully.
  2023-07-22 16:13:33 App\Jobs\TestJob ............................... RUNNING
  2023-07-22 16:13:34 App\Jobs\TestJob ............................... 1s DONE
  2023-07-22 16:13:39 App\Jobs\TestJob ............................... RUNNING
  2023-07-22 16:13:39 App\Jobs\TestJob ........................... 3.67ms FAIL
EOF);
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::HORIZON); // HorizonLog

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(4)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2023-07-22 16:13:33')
        ->and($logs[0]->level)->toBe('RUNNING')
        ->and($logs[0]->getLevel())->toBeInstanceOf(HorizonStatusLevel::class)
        ->and($logs[0]->getLevel()->getName())->toBe('Running')
        ->and($logs[0]->message)->toBe('App\Jobs\TestJob')
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2023-07-22 16:13:34')
        ->and($logs[1]->level)->toBe('DONE')
        ->and($logs[1]->message)->toBe('App\Jobs\TestJob')
        ->and($logs[1]->context['duration'])->toBe('1s');
});

it('processes weird cases', function () {
    $file = generateLogFile(content: <<<EOF
Horizon started successfully.
  2023-08-17 07:22:32 App\Jobs\TestJob  1 s DONE
  2023-08-18 06:05:03 App\Jobs\TestJob  32 s. DONE
  2023-08-19 04:31:07 App\Jobs\TestJob .... 12 sek DONE
  2023-08-20 02:15:58 App\Jobs\TestJob  2023-08-20 02:15:59 App\Jobs\TestJob  25.14ms DONE
EOF);
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::HORIZON); // HorizonLog

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(4)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2023-08-17 07:22:32')
        ->and($logs[0]->level)->toBe('DONE')
        ->and($logs[0]->getLevel())->toBeInstanceOf(HorizonStatusLevel::class)
        ->and($logs[0]->getLevel()->getName())->toBe('Done')
        ->and($logs[0]->message)->toBe('App\Jobs\TestJob')
        ->and($logs[0]->context['duration'])->toBe('1 s')

        ->and($logs[1]->datetime->toDateTimeString())->toBe('2023-08-18 06:05:03')
        ->and($logs[1]->level)->toBe('DONE')
        ->and($logs[1]->message)->toBe('App\Jobs\TestJob')
        ->and($logs[1]->context['duration'])->toBe('32 s.')

        ->and($logs[2]->datetime->toDateTimeString())->toBe('2023-08-19 04:31:07')
        ->and($logs[2]->level)->toBe('DONE')
        ->and($logs[2]->message)->toBe('App\Jobs\TestJob')
        ->and($logs[2]->context['duration'])->toBe('12 sek')

        // this one is weird, but let's drop the incomplete beginning and assume the rest of the line is
        // the correct log entry.
        ->and($logs[3]->datetime->toDateTimeString())->toBe('2023-08-20 02:15:59')
        ->and($logs[3]->level)->toBe('DONE')
        ->and($logs[3]->message)->toBe('App\Jobs\TestJob')
        ->and($logs[3]->context['duration'])->toBe('25.14ms');
});
