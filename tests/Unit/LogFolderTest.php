<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogFolder;

test('LogFolder can get the earliest timestamp of the files it contains', function () {
    $firstFile = mock(new LogFile('test.log', 'folder'))
        ->expect(earliestTimestamp: fn () => now()->subDay()->timestamp);
    $secondFile = mock(new LogFile('test2.log', 'folder'))
        ->expect(earliestTimestamp: fn () => now()->subDays(2)->timestamp);
    $folder = new LogFolder('folder', [$firstFile, $secondFile]);

    expect($folder->earliestTimestamp())->toBe($secondFile->earliestTimestamp());
});

test('LogFolder can get the latest timestamp of the files it contains', function () {
    $firstFile = mock(new LogFile('test.log', 'folder'))
        ->expect(latestTimestamp: fn () => now()->subDay()->timestamp);
    $secondFile = mock(new LogFile('test2.log', 'folder'))
        ->expect(latestTimestamp: fn () => now()->subDays(2)->timestamp);
    $folder = new LogFolder('folder', [$firstFile, $secondFile]);

    expect($folder->latestTimestamp())->toBe($firstFile->latestTimestamp());
});
