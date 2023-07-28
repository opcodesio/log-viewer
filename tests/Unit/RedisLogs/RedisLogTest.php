<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogLevels\RedisLogLevel;
use Opcodes\LogViewer\Logs\LogType;

it('can process Redis logs', function () {
    $file = generateLogFile('redis.log', <<<'LOG'
18696:C 17 Jul 2021 17:34:22.968 # Configuration loaded
18696:M 17 Jul 2021 17:34:22.970 * Increased maximum number of open files to 10032 (it was originally set to 256).
18696:X 17 Jul 2021 17:34:22.971 - monotonic clock: POSIX clock_gettime
18696:S 17 Jul 2021 17:34:22.971 . ticks per second: 100
LOG);

    /** LEVELS:
        . debug
        - verbose
        * notice
        # warning
     */

    /** ROLES:
        X sentinel
        S slave
        M master
        C RDB/AOF writing child
     */
    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::REDIS);

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(4)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2021-07-17 17:34:22')
        ->and($logs[0]->level)->toBe('#')
        ->and($logs[0]->getLevel())->toBeInstanceOf(RedisLogLevel::class)
        ->and($logs[0]->getLevel()->getName())->toBe('Warning')
        ->and($logs[0]->message)->toBe('Configuration loaded')
        ->and($logs[0]->context['role'])->toBe('C')
        ->and($logs[0]->context['role_description'])->toBe('RDB/AOF writing child')
        ->and($logs[0]->context['pid'])->toBe(18696)

        ->and($logs[1]->datetime->toDateTimeString())->toBe('2021-07-17 17:34:22')
        ->and($logs[1]->level)->toBe('*')
        ->and($logs[1]->getLevel())->toBeInstanceOf(RedisLogLevel::class)
        ->and($logs[1]->getLevel()->getName())->toBe('Notice')
        ->and($logs[1]->message)->toBe('Increased maximum number of open files to 10032 (it was originally set to 256).')
        ->and($logs[1]->context['role'])->toBe('M')
        ->and($logs[1]->context['role_description'])->toBe('master')
        ->and($logs[1]->context['pid'])->toBe(18696)

        ->and($logs[2]->datetime->toDateTimeString())->toBe('2021-07-17 17:34:22')
        ->and($logs[2]->level)->toBe('-')
        ->and($logs[2]->getLevel())->toBeInstanceOf(RedisLogLevel::class)
        ->and($logs[2]->getLevel()->getName())->toBe('Verbose')
        ->and($logs[2]->message)->toBe('monotonic clock: POSIX clock_gettime')
        ->and($logs[2]->context['role'])->toBe('X')
        ->and($logs[2]->context['role_description'])->toBe('sentinel')
        ->and($logs[2]->context['pid'])->toBe(18696)

        ->and($logs[3]->datetime->toDateTimeString())->toBe('2021-07-17 17:34:22')
        ->and($logs[3]->level)->toBe('.')
        ->and($logs[3]->getLevel())->toBeInstanceOf(RedisLogLevel::class)
        ->and($logs[3]->getLevel()->getName())->toBe('Debug')
        ->and($logs[3]->message)->toBe('ticks per second: 100')
        ->and($logs[3]->context['pid'])->toBe(18696)
        ->and($logs[3]->context['role'])->toBe('S')
        ->and($logs[3]->context['role_description'])->toBe('slave');
});
