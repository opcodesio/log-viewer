<?php

use Illuminate\Support\Facades\Cache;
use Opcodes\LogViewer\Facades\LogViewer;
use function PHPUnit\Framework\assertNotSame;

test('clearing file cache will clear the related cache keys', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $file = LogViewer::getFile($fileName);
    Cache::put($cacheKey = 'some-cache-key', 'some value');
    $file->addRelatedCacheKey($cacheKey);
    expect(Cache::has($cacheKey))->toBeTrue();

    $file->clearCache();

    expect(Cache::has($cacheKey))->toBeFalse();
});

test('does not clear cache of a different log file', function () {
    generateLogFiles([$fileName = 'laravel.log', $secondFileName = 'second.log']);
    $file = LogViewer::getFile($fileName);
    $secondFile = LogViewer::getFile($secondFileName);
    Cache::put($secondCacheKey = 'second-file-cache-key', 'some value');
    $secondFile->addRelatedCacheKey($secondCacheKey);
    expect(Cache::has($secondCacheKey))->toBeTrue();

    $file->clearCache();

    expect(Cache::has($secondCacheKey))->toBeTrue();
});

test('retains related cache keys after object re-initialisation', function () {
    generateLogFiles([$fileName = 'laravel.log']);
    $file = LogViewer::getFile($fileName);
    Cache::put($cacheKey = 'some-cache-key', 'some value');
    $file->addRelatedCacheKey($cacheKey);
    expect(Cache::has($cacheKey))->toBeTrue();

    // let's reinitialise the file object by clearing the log viewer log file cache
    LogViewer::clearFileCache();
    $newFileObject = LogViewer::getFile($fileName);
    // to make sure the object is not the exact same one:
    assertNotSame($file, $newFileObject);

    // make sure to call the method on the NEW instance, which we want to have remembered the related cache keys
    $newFileObject->clearCache();

    // even after re-initialisation, the LogFile object should know what related cache keys it has.
    expect(Cache::has($cacheKey))->toBeFalse();
});
