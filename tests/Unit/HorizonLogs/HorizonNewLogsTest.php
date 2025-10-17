<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogLevels\HorizonStatusLevel;
use Opcodes\LogViewer\Logs\LogType;

it('can process new Horizon logs', function () {
    $file = generateLogFile('horizon_new.log', content: <<<EOF
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
    $file = generateLogFile('horizon_new_weird.log', content: <<<EOF
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

it('detects Horizon logs without preamble', function () {
    $file = generateLogFile('horizon_no_preamble.log', content: <<<EOF
  2023-07-22 16:13:33 App\Jobs\ProcessOrder ............................... RUNNING
  2023-07-22 16:13:34 App\Jobs\ProcessOrder ............................... 1.2s DONE
  2023-07-22 16:13:35 App\Jobs\SendInvoice ................................ RUNNING
  2023-07-22 16:13:36 App\Jobs\SendInvoice ................................ 850ms DONE
EOF);
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::HORIZON);

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(4)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2023-07-22 16:13:33')
        ->and($logs[0]->level)->toBe('RUNNING')
        ->and($logs[0]->message)->toBe('App\Jobs\ProcessOrder')
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2023-07-22 16:13:34')
        ->and($logs[1]->level)->toBe('DONE')
        ->and($logs[1]->context['duration'])->toBe('1.2s')
        ->and($logs[2]->level)->toBe('RUNNING')
        ->and($logs[3]->level)->toBe('DONE')
        ->and($logs[3]->context['duration'])->toBe('850ms');
});

it('handles various duration formats', function () {
    $file = generateLogFile('horizon_durations.log', content: <<<EOF
  2022-11-11 13:50:44 App\Jobs\FirstJob ............... 40.12ms DONE
  2022-11-11 13:50:44 App\Jobs\SecondJob .............. 35.42ms DONE
  2022-11-11 13:50:44 App\Jobs\ThirdJob ............... 71.48ms DONE
  2022-11-11 13:50:44 App\Jobs\FourthJob .............. 55.35ms DONE
  2022-11-11 13:50:45 App\Jobs\FifthJob ............... 3.67ms FAIL
  2022-11-11 13:50:46 App\Jobs\SixthJob ............... 2m 30s DONE
EOF);
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::HORIZON);

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(6)
        ->and($logs[0]->context['duration'])->toBe('40.12ms')
        ->and($logs[0]->level)->toBe('DONE')
        ->and($logs[1]->context['duration'])->toBe('35.42ms')
        ->and($logs[1]->level)->toBe('DONE')
        ->and($logs[2]->context['duration'])->toBe('71.48ms')
        ->and($logs[2]->level)->toBe('DONE')
        ->and($logs[3]->context['duration'])->toBe('55.35ms')
        ->and($logs[3]->level)->toBe('DONE')
        ->and($logs[4]->context['duration'])->toBe('3.67ms')
        ->and($logs[4]->level)->toBe('FAIL')
        ->and($logs[5]->context['duration'])->toBe('2m 30s')
        ->and($logs[5]->level)->toBe('DONE');
});

it('handles concurrent job output with garbled lines', function () {
    $file = generateLogFile('horizon_concurrent.log', content: <<<EOF
  2022-11-11 13:50:44 App\Jobs\SlowJob ................ RUNNING
  2022-11-11 13:50:44 App\Jobs\QuickJob ............... 40.12ms DONE
  2022-11-11 13:50:44 App\Jobs\AnotherJob ............. 35.42ms DONE
  2022-11-11 13:50:44 App\Jobs\SlowJob 2022-11-11 13:50:44 App\Notifications\SomeNotification
  2022-11-11 13:50:44 App\Notifications\SomeNotification ..................... 71.48ms DONE
  2022-11-11 13:50:44 App\Jobs\SlowJob ..................... 55.35ms DONE
EOF);
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::HORIZON);

    $logReader = $file->logs()->scan();

    $logs = $logReader->get();

    // File should be detected as Horizon type
    expect($file->type()->value)->toBe(LogType::HORIZON);

    // Valid lines should be parsed correctly (garbled line might be skipped or partially parsed)
    expect($logs)->toBeArray()
        ->and(count($logs))->toBeGreaterThanOrEqual(4);

    // Check that at least some valid logs were parsed
    $validLogs = array_filter($logs, fn($log) => $log->datetime !== null);
    expect(count($validLogs))->toBeGreaterThanOrEqual(4);
});
