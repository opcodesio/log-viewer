<?php

use Illuminate\Support\Facades\File;
use Opcodes\LogViewer\LogReader;

beforeEach(function () {
    $this->file = generateLogFile();
    File::append($this->file->path, makeLaravelLogEntry());
});

it('can scan a log file', function () {
    $logReader = $this->file->logs();
    expect($logReader->requiresScan())->toBeTrue();

    $logReader->scan();

    $index = $this->file->index();

    expect($logReader->requiresScan())->toBeFalse()
        ->and($index->count())->toBe(1);
});

it('can re-scan the file after a new entry has been added', function () {
    $logReader = $this->file->logs();
    $logReader->scan();

    \Spatie\TestTime\TestTime::addMinute();

    File::append($this->file->path, PHP_EOL.makeLaravelLogEntry());

    // re-instantiate the log reader to make sure we don't have anything cached
    LogReader::clearInstance($this->file);
    $logReader = $this->file->logs();
    expect($logReader->requiresScan())->toBeTrue();

    $logReader->scan();
    $index = $this->file->index();

    expect($logReader->requiresScan())->toBeFalse()
        ->and($index->count())->toBe(2)
        ->and($index->getFlatIndex())->toHaveCount(2);
});
