<?php

use Opcodes\LogViewer\LogLevels\LaravelLogLevel;

it('can return the counts for each severity level in the file', function () {
    $logIndex = createLogIndex(predefinedLogs: [
        [0, now()->subDays(3), 'INFO'],
        [1500, now()->subDays(2), 'INFO'],
        $latestLog = [3000, now()->subDay(), 'ERROR'],
    ]);

    $logCounts = $logIndex->getLevelCounts();

    expect($logCounts)->toHaveCount(2)
        ->and($logCounts[LaravelLogLevel::Info])->toBe(2)
        ->and($logCounts[LaravelLogLevel::Error])->toBe(1);
});

it('can return the smaller counts with date filter applied', function () {
    $logIndex = createLogIndex(predefinedLogs: [
        [0, now()->subDays(3), 'INFO'],
        [1500, now()->subDays(2), 'INFO'],
        $latestLog = [3000, now()->subDay(), 'ERROR'],
    ]);

    // the first info log will be skipped from the counts
    $logCounts = $logIndex->forDateRange(from: now()->subDays(2)->subHour())->getLevelCounts();

    expect($logCounts[LaravelLogLevel::Info])->toBe(1)
        ->and($logCounts[LaravelLogLevel::Error])->toBe(1);
});
