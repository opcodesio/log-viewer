<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogFileCollection;
use Opcodes\LogViewer\LogFolder;

test('LogViewer::getFiles() returns a LogFileCollection', function () {
    expect(LogViewer::getFiles())
        ->toBeInstanceOf(LogFileCollection::class);
});

test('LogFileCollection can sort its files by earliest logs first', function () {
    $firstFile = mock(new LogFile('test.log', realpath(storage_path('test.log'))))
        ->shouldReceive('earliestTimestamp')->andReturn(now()->subDay()->timestamp)->getMock();
    $secondFile = mock(new LogFile('test2.log', realpath(storage_path('test2.log'))))
        ->shouldReceive('earliestTimestamp')->andReturn(now()->subDays(2)->timestamp)->getMock();
    $collection = new LogFileCollection([$firstFile, $secondFile]);

    $collection->sortByEarliestFirst();

    expect($collection[0])->toBe($secondFile)
        ->and($collection[1])->toBe($firstFile);
});

test('LogFileCollection can sort its files by latest logs first', function () {
    $firstFile = mock(new LogFile('test.log', realpath(storage_path('test.log'))))
        ->shouldReceive('latestTimestamp')->andReturn(now()->subDays(2)->timestamp)->getMock();
    $secondFile = mock(new LogFile('test2.log', realpath(storage_path('test2.log'))))
        ->shouldReceive('latestTimestamp')->andReturn(now()->subDay()->timestamp)->getMock();
    $collection = new LogFileCollection([$firstFile, $secondFile]);

    $collection->sortByLatestFirst();

    expect($collection[0])->toBe($secondFile)
        ->and($collection[1])->toBe($firstFile);
});

test('LogFolder::files() returns a LogFileCollection', function () {
    $folder = new LogFolder(storage_path('subfolder'), []);

    expect($folder->files())->toBeInstanceOf(LogFileCollection::class);
});

test('LogFileCollection can return the latest log file', function () {
    $firstFile = mock(new LogFile('test.log', realpath(storage_path('test.log'))))
        ->shouldReceive('latestTimestamp')->andReturn(now()->subDays(2)->timestamp)->getMock();
    $secondFile = mock(new LogFile('test2.log', realpath(storage_path('test2.log'))))
        ->shouldReceive('latestTimestamp')->andReturn(now()->subDay()->timestamp)->getMock();
    $collection = new LogFileCollection([$firstFile, $secondFile]);

    $logFile = $collection->latest();

    expect($logFile)->toBe($secondFile);
});

test('LogFileCollection can return the earliest log file', function () {
    $firstFile = mock(new LogFile('test.log', realpath(storage_path('test.log'))))
        ->shouldReceive('earliestTimestamp')->andReturn(now()->subDay()->timestamp)->getMock();
    $secondFile = mock(new LogFile('test2.log', realpath(storage_path('test2.log'))))
        ->shouldReceive('earliestTimestamp')->andReturn(now()->subDays(2)->timestamp)->getMock();
    $collection = new LogFileCollection([$firstFile, $secondFile]);

    $logFile = $collection->earliest();

    expect($logFile)->toBe($secondFile);
});
