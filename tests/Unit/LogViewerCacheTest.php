<?php

use Illuminate\Cache\FileStore;
use Illuminate\Cache\RedisStore;

it('it defaults to the app\'s default cache driver', function ($cacheType, $cacheStoreClass) {
    config(['cache.default' => $cacheType]);

    expect(\Opcodes\LogViewer\Facades\Cache::getStore())
        ->toBeInstanceOf($cacheStoreClass);
})->with([
    ['file', FileStore::class],
    ['redis', RedisStore::class],
]);

it('can provide a different cache driver for the log viewer', function () {
    config(['cache.default' => 'redis']);
    config(['log-viewer.cache_driver' => 'file']);

    expect(\Opcodes\LogViewer\Facades\Cache::getStore())
        ->toBeInstanceOf(FileStore::class);
});
