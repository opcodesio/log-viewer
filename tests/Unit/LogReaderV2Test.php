<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogReaderV2;
use Opcodes\LogViewer\Logs\HttpAccessLog;
use Opcodes\LogViewer\Logs\LaravelLog;
use Opcodes\LogViewer\Logs\LogType;

beforeEach(function () {
    $this->file = generateLogFile();
    File::append($this->file->path, makeLaravelLogEntry());

    LogViewer::useLogReaderClass(LogReaderV2::class);
});

it('can scan a log file', function () {
    $logReader = $this->file->logs();
    expect($logReader->requiresScan())->toBeTrue();

    $logReader->scan();

    $index = $this->file->index(indexClass: \Opcodes\LogViewer\LogIndexV2::class);

    expect($logReader->requiresScan())->toBeFalse()
        ->and($index->count())->toBe(1);
});

it('can re-scan the file after a new entry has been added', function () {
    $logReader = $this->file->logs();
    $logReader->scan();

    \Spatie\TestTime\TestTime::addMinute();

    File::append($this->file->path, PHP_EOL.makeLaravelLogEntry());

    // re-instantiate the log reader to make sure we don't have anything cached
    LogReaderV2::clearInstance($this->file);
    $logReader = $this->file->logs();
    expect($logReader->requiresScan())->toBeTrue();

    $logReader->scan();
    expect($logReader->requiresScan())->toBeFalse()
        ->and($this->file->index(indexClass: \Opcodes\LogViewer\LogIndexV2::class)->count())->toBe(2);
});

it('can get the next log entry', function () {
    $file = generateLogFile(content: implode("\n", [
        $log1 = makeHttpAccessLogEntry(),
        $log2 = makeHttpAccessLogEntry(),
        $log3 = makeHttpAccessLogEntry(),
    ]), type: LogType::HTTP_ACCESS);
    $logReader = $file->logs()->scan();

    $log = $logReader->next();

    expect($log)->toBeInstanceOf(HttpAccessLog::class)
        ->and($log->getOriginalText())->toBe($log1);

    $secondLog = $logReader->next();

    expect($secondLog)->toBeInstanceOf(HttpAccessLog::class)
        ->and($secondLog->getOriginalText())->toBe($log2);

    $thirdLog = $logReader->next();

    expect($thirdLog)->toBeInstanceOf(HttpAccessLog::class)
        ->and($thirdLog->getOriginalText())->toBe($log3);
});

it('can skip a number of entries', function () {
    $file = generateLogFile(content: implode("\n", [
        $log1 = makeHttpAccessLogEntry(),
        $log2 = makeHttpAccessLogEntry(),
        $log3 = makeHttpAccessLogEntry(),
    ]), type: LogType::HTTP_ACCESS);
    $logReader = $file->logs()->scan();

    $logReader->skip(2);

    $thirdLog = $logReader->next();

    expect($thirdLog)->toBeInstanceOf(HttpAccessLog::class)
        ->and($thirdLog->getOriginalText())->toBe($log3);
});

it('can skip a certain levels', function () {
    $file = generateLogFile(content: implode("\n", [
        $log1 = makeHttpAccessLogEntry(statusCode: 200),
        $log2 = makeHttpAccessLogEntry(statusCode: 200),
        $log3 = makeHttpAccessLogEntry(statusCode: 404),
    ]), type: LogType::HTTP_ACCESS);
    $logReader = $file->logs()->scan();

    $logReader->exceptLevels('200');

    $thirdLog = $logReader->next();

    expect($thirdLog)->toBeInstanceOf(HttpAccessLog::class)
        ->and($thirdLog->getOriginalText())->toBe($log3);
});

it('can get the logs backwards', function () {
    $file = generateLogFile(content: implode("\n", [
        $log1 = makeHttpAccessLogEntry(),
        $log2 = makeHttpAccessLogEntry(),
        $log3 = makeHttpAccessLogEntry(),
    ]), type: LogType::HTTP_ACCESS);
    $logReader = $file->logs()->scan();

    $logReader->reverse();

    $log = $logReader->next();

    expect($log)->toBeInstanceOf(HttpAccessLog::class)
        ->and($log->getOriginalText())->toBe($log3);

    $secondLog = $logReader->next();

    expect($secondLog)->toBeInstanceOf(HttpAccessLog::class)
        ->and($secondLog->getOriginalText())->toBe($log2);

    $thirdLog = $logReader->next();

    expect($thirdLog)->toBeInstanceOf(HttpAccessLog::class)
        ->and($thirdLog->getOriginalText())->toBe($log1);
});

it('can filter the logs while reading backwards', function () {
    $file = generateLogFile(content: implode("\n", [
        $log1 = makeHttpAccessLogEntry(statusCode: 404),
        $log2 = makeHttpAccessLogEntry(statusCode: 200),
        $log3 = makeHttpAccessLogEntry(statusCode: 200),
    ]), type: LogType::HTTP_ACCESS);
    $logReader = $file->logs()->scan();

    $logReader->reverse()->exceptLevels('200');

    $thirdLog = $logReader->next();

    expect($thirdLog)->toBeInstanceOf(HttpAccessLog::class)
        ->and($thirdLog->getOriginalText())->toBe($log1);
});
