<?php

use function Pest\Laravel\getJson;

it('can load the logs for a specific file', function () {
    $logEntries = [
        makeLaravelLogEntry(),
        makeLaravelLogEntry(),
        makeLaravelLogEntry(),
    ];
    $file = generateLogFile('logcontrollertest.log', implode(PHP_EOL, $logEntries));

    $response = getJson(route('log-viewer.logs', ['file' => $file->identifier]));

    expect($response->json('logs'))->toHaveCount(count($logEntries));
});

test('simple characters can be searched case-insensitive', function () {
    $logEntries = [
        makeLaravelLogEntry(message: 'error'),
        makeLaravelLogEntry(message: 'Error'),
        makeLaravelLogEntry(message: 'eRrOr'),
        makeLaravelLogEntry(message: 'ERROR'),
        makeLaravelLogEntry(message: 'simple text'),
    ];
    $file = generateLogFile('logsearchtest.log', implode(PHP_EOL, $logEntries));

    // first, just to be sure that we're getting all the logs without any query
    $response = getJson(route('log-viewer.logs', ['file' => $file->identifier]));
    expect($response->json('logs'))->toHaveCount(count($logEntries));

    // now, with the query. Re-instantiate the log reader to make sure we don't have anything cached.
    \Opcodes\LogViewer\Readers\IndexedLogReader::clearInstance($file);
    $response = getJson(route('log-viewer.logs', [
        'file' => $file->identifier,
        'query' => 'error',
    ]));
    expect($response->json('logs'))->toHaveCount(4);

});

test('unicode characters can be searched case-insensitive', function () {
    $logEntries = [
        makeLaravelLogEntry(message: 'ошибка'),
        makeLaravelLogEntry(message: 'Ошибка'),
        makeLaravelLogEntry(message: 'ошибкА'),
        makeLaravelLogEntry(message: 'ОШИБКА'),
        makeLaravelLogEntry(message: 'simple text'),
    ];
    $file = generateLogFile('logunicodetest.log', implode(PHP_EOL, $logEntries));

    // first, just to be sure that we're getting all the logs without any query
    $response = getJson(route('log-viewer.logs', ['file' => $file->identifier]));
    expect($response->json('logs'))->toHaveCount(count($logEntries));

    // now, with the query. Re-instantiate the log reader to make sure we don't have anything cached.
    \Opcodes\LogViewer\Readers\IndexedLogReader::clearInstance($file);
    $response = getJson(route('log-viewer.logs', [
        'file' => $file->identifier,
        'query' => 'ошибка',
    ]));
    expect($response->json('logs'))->toHaveCount(4);
});

test('logs include full_text property by default', function () {
    $logEntries = [
        makeLaravelLogEntry(message: 'Test message'),
    ];
    $file = generateLogFile('log_with_full_text.log', implode(PHP_EOL, $logEntries));

    $response = getJson(route('log-viewer.logs', ['file' => $file->identifier]));

    expect($response->json('logs'))->toHaveCount(1);
    expect($response->json('logs.0'))->toHaveKey('full_text');
});

test('logs can exclude full_text property when requested', function () {
    $logEntries = [
        makeLaravelLogEntry(message: 'Test message'),
    ];
    $file = generateLogFile('log_without_full_text.log', implode(PHP_EOL, $logEntries));

    $response = getJson(route('log-viewer.logs', [
        'file' => $file->identifier,
        'exclude_full_text' => true,
    ]));

    expect($response->json('logs'))->toHaveCount(1);
    expect($response->json('logs.0'))->not->toHaveKey('full_text');
});
