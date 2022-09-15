<?php

use Opcodes\LogViewer\LogFile;

it('can generate a cache key', function () {
    $file = Mockery::mock(new LogFile('test.log', 'test.log'))
        ->allows(['cacheKey' => $fileCacheKey = 'file-cache-key']);
    $logIndex = createLogIndex($file);

    expect($logIndex->cacheKey())
        ->toBe($fileCacheKey.':'.md5('').':log-index');

    // if we instead create an index for a particular search,
    // the cache key would be different:
    $logIndex = createLogIndex($file, $query = 'some-query');

    expect($logIndex->cacheKey())
        ->toBe($fileCacheKey.':'.md5($query).':log-index');
});

it('can generate a cache key for metadata', function () {
    $file = Mockery::mock(new LogFile('test.log', 'test.log'))
        ->allows(['cacheKey' => $fileCacheKey = 'file-cache-key']);
    $logIndex = createLogIndex($file);

    expect($logIndex->metaCacheKey())
        ->toBe($fileCacheKey.':'.md5('').':metadata');

    // if we instead create an index for a particular search,
    // the cache key would be different:
    $logIndex = createLogIndex($file, $query = 'some-query');

    expect($logIndex->metaCacheKey())
        ->toBe($fileCacheKey.':'.md5($query).':metadata');
});
