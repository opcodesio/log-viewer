<?php

use Opcodes\LogViewer\Level;

it('can return the counts for each severity level in the file', function () {
    $logIndex = createLogIndex(predefinedLogs: [
        [0, now()->subDays(3), 'info'],
        [1500, now()->subDays(2), 'info'],
        $latestLog = [3000, now()->subDay(), 'error'],
    ]);

    $logCounts = $logIndex->getLevelCounts();

    foreach (Level::caseValues() as $caseValue) {
        if ($caseValue === Level::Info) {
            expect($logCounts[Level::Info])->toBe(2);
        } elseif ($caseValue === Level::Error) {
            expect($logCounts[Level::Error])->toBe(1);
        } else {
            // we still want the rest of the level counts to be set
            expect($logCounts[$caseValue])->toBe(0);
        }
    }
});

it('can return the smaller counts with date filter applied', function () {
    $logIndex = createLogIndex(predefinedLogs: [
        [0, now()->subDays(3), 'info'],
        [1500, now()->subDays(2), 'info'],
        $latestLog = [3000, now()->subDay(), 'error'],
    ]);

    // the first info log will be skipped from the counts
    $logCounts = $logIndex->forDateRange(from: now()->subDays(2)->subHour())->getLevelCounts();

    expect($logCounts[Level::Info])->toBe(1)
        ->and($logCounts[Level::Error])->toBe(1);
});
