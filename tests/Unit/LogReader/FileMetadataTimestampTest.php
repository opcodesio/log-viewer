<?php

use Carbon\Carbon;

it('preserves file metadata timestamps when scanning with a search query', function () {
    // Create a log file with entries spanning multiple dates
    $earliestDate = Carbon::parse('2024-01-01 10:00:00');
    $middleDate = Carbon::parse('2024-01-15 12:00:00');
    $latestDate = Carbon::parse('2024-01-31 14:00:00');

    $logContent = implode(PHP_EOL, [
        makeLaravelLogEntry($earliestDate, 'info', 'First log entry'),
        makeLaravelLogEntry($middleDate, 'error', 'Error occurred here'),
        makeLaravelLogEntry($latestDate, 'info', 'Last log entry'),
    ]);

    $logFile = generateLogFile('test-metadata.log', $logContent);

    // Initially scan without query to establish baseline metadata
    $logReader = $logFile->logs();
    $logReader->scan();
    
    $initialEarliest = $logFile->earliestTimestamp();
    $initialLatest = $logFile->latestTimestamp();

    expect($initialEarliest)->toBe($earliestDate->timestamp);
    expect($initialLatest)->toBe($latestDate->timestamp);

    // Now scan with a query that only matches the middle log
    $logReaderWithQuery = $logFile->logs();
    $logReaderWithQuery->search('/Error occurred/');
    $logReaderWithQuery->scan();

    // File metadata should still reflect ALL logs, not just matching ones
    $finalEarliest = $logFile->earliestTimestamp();
    $finalLatest = $logFile->latestTimestamp();

    expect($finalEarliest)->toBe($earliestDate->timestamp)
        ->and($finalLatest)->toBe($latestDate->timestamp)
        ->and($finalEarliest)->toBe($initialEarliest)
        ->and($finalLatest)->toBe($initialLatest);
});

it('updates file metadata timestamps correctly when scanning without query', function () {
    $earliestDate = Carbon::parse('2024-01-01 10:00:00');
    $latestDate = Carbon::parse('2024-01-31 14:00:00');

    $logContent = implode(PHP_EOL, [
        makeLaravelLogEntry($earliestDate, 'info', 'First log entry'),
        makeLaravelLogEntry($latestDate, 'info', 'Last log entry'),
    ]);

    $logFile = generateLogFile('test-metadata-update.log', $logContent);

    // Scan without query
    $logReader = $logFile->logs();
    $logReader->scan();

    expect($logFile->earliestTimestamp())->toBe($earliestDate->timestamp)
        ->and($logFile->latestTimestamp())->toBe($latestDate->timestamp);
});

it('does not overwrite file metadata with filtered timestamps when query matches subset', function () {
    // Create logs with dates spanning a month, but query only matches middle logs
    $date1 = Carbon::parse('2024-01-01 10:00:00'); // Won't match query
    $date2 = Carbon::parse('2024-01-15 12:00:00'); // Will match query
    $date3 = Carbon::parse('2024-01-20 13:00:00'); // Will match query
    $date4 = Carbon::parse('2024-01-31 14:00:00'); // Won't match query

    $logContent = implode(PHP_EOL, [
        makeLaravelLogEntry($date1, 'info', 'First log entry'),
        makeLaravelLogEntry($date2, 'error', 'Error: something went wrong'),
        makeLaravelLogEntry($date3, 'error', 'Error: another issue'),
        makeLaravelLogEntry($date4, 'info', 'Last log entry'),
    ]);

    $logFile = generateLogFile('test-filtered-metadata.log', $logContent);

    // Scan with query that only matches logs with "Error:" in them
    $logReader = $logFile->logs();
    $logReader->search('/Error:/');
    $logReader->scan();

    // File metadata should show full date range (Jan 1 - Jan 31), not filtered range (Jan 15 - Jan 20)
    // This is the core bug fix: file metadata should reflect ALL logs, not just filtered ones
    expect($logFile->earliestTimestamp())->toBe($date1->timestamp)
        ->and($logFile->latestTimestamp())->toBe($date4->timestamp);
});


