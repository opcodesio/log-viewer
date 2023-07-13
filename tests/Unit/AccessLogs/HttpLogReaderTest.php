<?php

use Opcodes\LogViewer\HttpAccessLog;
use Opcodes\LogViewer\HttpApacheErrorLog;
use Opcodes\LogViewer\HttpLogReader;
use Opcodes\LogViewer\LogFile;

it('can read a specific file', function () {
    $accessLogs = file_get_contents(__DIR__.'/Fixtures/access_dummy.log');
    $file = generateLogFile('access.log', $accessLogs, type: LogFile::TYPE_HTTP_ACCESS);

    $httpLogReader = new HttpLogReader($file);

    $logs = $httpLogReader->get();

    expect($logs)->toBeArray()
        ->and($logs)->toHaveCount(10)
        ->and($logs[0])->toBeInstanceOf(HttpAccessLog::class);
});

it('returns the correct log instance based on file type', function (string $type, string $expectedClass) {
    $file = generateLogFile('http.log', randomContent: true, type: $type);

    $httpLogReader = new HttpLogReader($file);

    $logs = $httpLogReader->get();

    expect($logs[0])->toBeInstanceOf($expectedClass);
})->with([
    ['type' => LogFile::TYPE_HTTP_ACCESS, 'expectedClass' => HttpAccessLog::class],
    ['type' => LogFile::TYPE_HTTP_ERROR_APACHE, 'expectedClass' => HttpApacheErrorLog::class],
]);

it('can read one log at a time', function () {
    $lines = [
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
    ];
    $file = generateLogFile('http.log', implode("\n", $lines), type: LogFile::TYPE_HTTP_ACCESS);

    $httpLogReader = new HttpLogReader($file);

    foreach ($lines as $expectedLine) {
        $actualLine = $httpLogReader->next();
        expect($actualLine->text)->toBe($expectedLine);
    }
});

it('provides the correct file position in the log entry', function () {
    $lines = [
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
    ];

    $file = generateLogFile('http.log', implode("\n", $lines), type: LogFile::TYPE_HTTP_ACCESS);

    $httpLogReader = new HttpLogReader($file);

    $expectedPosition = 0;
    foreach ($lines as $expectedLine) {
        $actualLine = $httpLogReader->next();
        expect($actualLine->filePosition)->toBe($expectedPosition);
        $expectedPosition += strlen($expectedLine) + strlen("\n");
    }
});

it('provides the correct file position when reading backwards', function () {
    $lines = [
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
    ];

    $file = generateLogFile('http.log', implode("\n", $lines), type: LogFile::TYPE_HTTP_ACCESS);

    $httpLogReader = (new HttpLogReader($file))->reverse();

    // expected positions are reversed, because we're reading in reverse
    $expectedPositions = [
        strlen($lines[0]) + strlen("\n") + strlen($lines[1]) + strlen("\n"),
        strlen($lines[0]) + strlen("\n"),
        0,
    ];

    foreach ($expectedPositions as $expectedPosition) {
        $actualLine = $httpLogReader->next();
        expect($actualLine->filePosition)->toBe($expectedPosition);
    }
});

it('can skip a number of logs', function () {
    $lines = [
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
    ];
    $file = generateLogFile('http.log', implode("\n", $lines), type: LogFile::TYPE_HTTP_ACCESS);

    $httpLogReader = new HttpLogReader($file);
    $entry = $httpLogReader->skip(2)->next();

    expect($entry->text)->toBe($lines[2]);
});

it('can read in reverse direction', function () {
    $lines = [
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
    ];
    $file = generateLogFile('http.log', implode("\n", $lines), type: LogFile::TYPE_HTTP_ACCESS);

    $httpLogReader = (new HttpLogReader($file))->reverse();

    foreach (array_reverse($lines) as $expectedLine) {
        $actualLine = $httpLogReader->next();
        expect($actualLine->text)->toBe($expectedLine);
    }
});

it('can limit the number of logs to get', function () {
    $lines = [
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
    ];
    $file = generateLogFile('http.log', implode("\n", $lines), type: LogFile::TYPE_HTTP_ACCESS);

    $httpLogReader = new HttpLogReader($file);

    $entries = $httpLogReader->limit(2)->get();

    expect($entries)->toHaveCount(2)
        ->and($entries[0]->text)->toBe($lines[0])
        ->and($entries[1]->text)->toBe($lines[1]);
});

it('can limit the number of logs to get (second option)', function () {
    $lines = [
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
        makeHttpAccessLogEntry(),
    ];
    $file = generateLogFile('http.log', implode("\n", $lines), type: LogFile::TYPE_HTTP_ACCESS);

    $httpLogReader = new HttpLogReader($file);

    $entries = $httpLogReader->get(2);

    expect($entries)->toHaveCount(2)
        ->and($entries[0]->text)->toBe($lines[0])
        ->and($entries[1]->text)->toBe($lines[1]);
});
