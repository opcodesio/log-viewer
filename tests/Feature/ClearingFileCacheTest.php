<?php

use Opcodes\LogViewer\Facades\Cache;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogIndex;
use Opcodes\LogViewer\Utils\GenerateCacheKey;
use function PHPUnit\Framework\assertNotSame;

beforeEach(function () {
    generateLogFiles(['laravel.log', 'other.log']);
    $this->file = LogViewer::getFile('laravel.log');
    $this->otherFile = LogViewer::getFile('other.log');
});

test('clearing file cache will clear the related cache keys', function () {
    Cache::put($cacheKey = 'some-cache-key', 'some value');
    $this->file->addRelatedCacheKey($cacheKey);
    expect(Cache::has($cacheKey))->toBeTrue();

    $this->file->clearCache();

    expect(Cache::has($cacheKey))->toBeFalse();
});

test('does not clear cache of a different log file', function () {
    Cache::put($secondCacheKey = 'second-file-cache-key', 'some value');
    $this->otherFile->addRelatedCacheKey($secondCacheKey);
    expect(Cache::has($secondCacheKey))->toBeTrue();

    $this->file->clearCache();

    expect(Cache::has($secondCacheKey))->toBeTrue();
});

test('retains related cache keys after object re-initialisation', function () {
    Cache::put($cacheKey = 'some-cache-key', 'some value');
    $this->file->addRelatedCacheKey($cacheKey);
    expect(Cache::has($cacheKey))->toBeTrue();

    // let's reinitialise the file object by clearing the log viewer log file cache
    LogViewer::clearFileCache();
    $newFileObject = LogViewer::getFile($this->file->identifier);
    // to make sure the object is not the exact same one:
    assertNotSame($this->file, $newFileObject);

    // make sure to call the method on the NEW instance, which we want to have remembered the related cache keys
    $newFileObject->clearCache();

    // even after re-initialisation, the LogFile object should know what related cache keys it has.
    expect(Cache::has($cacheKey))->toBeFalse();
});

test('can clear cache of all files from the Livewire component', function () {
    Cache::put($cacheKey = 'some-cache-key', 'some value');
    $this->file->addRelatedCacheKey($cacheKey);

    \Livewire\Livewire::test('log-viewer::log-list')
        ->call('clearCacheAll')
        ->assertOk();

    expect(Cache::has($cacheKey))->toBeFalse();
});

test('clearing file cache will also clear the index cache', function () {
    $logIndex = new LogIndex($this->file);
    $logIndex->save();
    $metaCacheKey = GenerateCacheKey::for($logIndex, 'metadata');
    expect(Cache::has($metaCacheKey))->toBeTrue();

    $this->file->clearCache();

    expect(Cache::has($metaCacheKey))->toBeFalse();
});
