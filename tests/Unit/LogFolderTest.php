<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogFolder;

test('LogFolder can get the earliest timestamp of the files it contains', function () {
    $firstFile = mock(new LogFile('test.log', 'folder'))
        ->shouldReceive('earliestTimestamp')->andReturn(now()->subDay()->timestamp)->getMock();
    $secondFile = mock(new LogFile('test2.log', 'folder'))
        ->shouldReceive('earliestTimestamp')->andReturn(now()->subDays(2)->timestamp)->getMock();
    $folder = new LogFolder('folder', [$firstFile, $secondFile]);

    expect($folder->earliestTimestamp())->toBe($secondFile->earliestTimestamp());
});

test('LogFolder can get the latest timestamp of the files it contains', function () {
    $firstFile = mock(new LogFile('test.log', 'folder'))
        ->shouldReceive('latestTimestamp')->andReturn(now()->subDay()->timestamp)->getMock();
    $secondFile = mock(new LogFile('test2.log', 'folder'))
        ->shouldReceive('latestTimestamp')->andReturn(now()->subDays(2)->timestamp)->getMock();
    $folder = new LogFolder('folder', [$firstFile, $secondFile]);

    expect($folder->latestTimestamp())->toBe($firstFile->latestTimestamp());
});
