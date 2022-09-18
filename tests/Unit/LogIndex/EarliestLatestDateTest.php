<?php

it('can get the earliest date from the log index', function () {
    $logIndex = createLogIndex(null, null, [
        [0, now()->subDay(), 'info'],
        [1500, now()->subDays(2), 'debug'],
        $earliestLog = [3000, now()->subDays(3), 'error'],
    ]);

    $earliestDate = $logIndex->getEarliestDate();

    expect($earliestDate?->timestamp)->toBe($earliestLog[1]->timestamp);
});

it('can get the latest date from the log index', function () {
    $logIndex = createLogIndex(null, null, [
        [0, now()->subDays(3), 'info'],
        [1500, now()->subDays(2), 'debug'],
        $latestLog = [3000, now()->subDay(), 'error'],
    ]);

    $earliestDate = $logIndex->getLatestDate();

    expect($earliestDate?->timestamp)->toBe($latestLog[1]->timestamp);
});

it('can get the earliest date after severity filter is applied', function () {
    $logIndex = createLogIndex(null, null, [
        [0, now()->subDays(1), 'debug'],
        $earliestDebugLog = [1500, now()->subDays(2), 'debug'],
        [3000, now()->subDays(3), 'error'], // this would normally be the earliest
    ]);

    $earliestDate = $logIndex->forLevels('debug')->getEarliestDate();

    expect($earliestDate?->timestamp)->toBe($earliestDebugLog[1]->timestamp);
});

it('can get the latest date after severity filter is applied', function () {
    $logIndex = createLogIndex(null, null, [
        [0, now()->subDays(3), 'debug'],
        $latestDebugLog = [1500, now()->subDays(2), 'debug'],
        [3000, now()->subDay(), 'error'],
    ]);

    $latestDate = $logIndex->forLevels('debug')->getLatestDate();

    expect($latestDate?->timestamp)->toBe($latestDebugLog[1]->timestamp);
});
