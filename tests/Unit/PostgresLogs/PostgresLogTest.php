<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;

it('can process a Postgres log file', function () {
    $file = generateLogFile('postgres@14.log', <<<'LOG'
2022-11-26 15:30:59.975 EET [20532] CONTEXT:  PL/pgSQL function inline_code_block line 16 at RAISE
2022-11-26 15:32:04.398 EET [18913] WARNING:
	WELCOME TO
	 _____ _                               _     ____________
	|_   _(_)                             | |    |  _  \ ___ \
	  | |  _ _ __ ___   ___  ___  ___ __ _| | ___| | | | |_/ /
	  | | | |  _ ` _ \ / _ \/ __|/ __/ _` | |/ _ \ | | | ___ \
	  | | | | | | | | |  __/\__ \ (_| (_| | |  __/ |/ /| |_/ /
	  |_| |_|_| |_| |_|\___||___/\___\__,_|_|\___|___/ \____/
	               Running version 2.8.1
2022-11-26 15:32:04.398 EET [18913] CONTEXT:  PL/pgSQL function inline_code_block line 16 at RAISE
2023-03-16 09:14:03.471 EET [8254] HINT:  Use datatype TIMESTAMPTZ instead.
2023-03-16 09:14:17.369 EET [49421] LOG:  received smart shutdown request
2023-03-16 09:14:22.372 EET [49433] FATAL:  postmaster exited while timescaledb scheduler was working
2023-03-16 09:14:22.376 EET [49430] FATAL:  postmaster exited while TimescaleDB background worker launcher was working
2023-03-16 09:14:22.380 EET [57580] FATAL:  terminating connection due to unexpected postmaster exit
2023-03-16 20:38:15.586 EET [16806] LOG:  starting PostgreSQL 14.7 (Homebrew) on aarch64-apple-darwin22.1.0, compiled by Apple clang version 14.0.0 (clang-1400.0.29.202), 64-bit
LOG);

    $file = new LogFile($file->path);

    expect($file->type()->value)->toBe(LogType::POSTGRES);

    $logReader = $file->logs()->scan();

    expect($logs = $logReader->get())->toHaveCount(9)
        ->and($logs[0]->datetime->toDateTimeString())->toBe('2022-11-26 15:30:59')
        ->and($logs[0]->level)->toBe('CONTEXT')
        ->and($logs[0]->message)->toBe('PL/pgSQL function inline_code_block line 16 at RAISE')
        ->and($logs[0]->context['pid'])->toBe(20532)
        ->and($logs[1]->datetime->toDateTimeString())->toBe('2022-11-26 15:32:04')
        ->and($logs[1]->level)->toBe('WARNING')
        ->and($logs[1]->message)->toBe('WELCOME TO')
        ->and($logs[1]->context['pid'])->toBe(18913)
        ->and($logs[2]->datetime->toDateTimeString())->toBe('2022-11-26 15:32:04')
        ->and($logs[2]->level)->toBe('CONTEXT')
        ->and($logs[2]->message)->toBe('PL/pgSQL function inline_code_block line 16 at RAISE')
        ->and($logs[2]->context['pid'])->toBe(18913);
});
