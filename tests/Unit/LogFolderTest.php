<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogFolder;

test('LogFolder can get the earliest timestamp of the files it contains', function () {
    $firstFile = Mockery::mock(new LogFile('folder/test.log'))
        ->allows(['earliestTimestamp' => now()->subDay()->timestamp]);
    $secondFile = Mockery::mock(new LogFile('folder/test2.log'))
        ->allows(['earliestTimestamp' => now()->subDays(2)->timestamp]);
    $folder = new LogFolder('folder', [$firstFile, $secondFile]);

    expect($folder->earliestTimestamp())->toBe($secondFile->earliestTimestamp());
});

test('LogFolder can get the latest timestamp of the files it contains', function () {
    $firstFile = Mockery::mock(new LogFile('folder/test.log'))
        ->allows(['latestTimestamp' => now()->subDay()->timestamp]);
    $secondFile = Mockery::mock(new LogFile('folder/test2.log'))
        ->allows(['latestTimestamp' => now()->subDays(2)->timestamp]);
    $folder = new LogFolder('folder', [$firstFile, $secondFile]);

    expect($folder->latestTimestamp())->toBe($firstFile->latestTimestamp());
});
