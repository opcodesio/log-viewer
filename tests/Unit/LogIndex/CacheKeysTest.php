<?php

use Mockery\MockInterface;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Utils\GenerateCacheKey;

it('uses GenerateCacheKey to generate a cache key', function () {
    $logFile = new LogFile('test.log');
    $this->mock(GenerateCacheKey::class, function (MockInterface $mock) use ($logFile) {
        $mock->shouldReceive('for')->with($logFile);
    });

    $logFile->clearCache();
});
