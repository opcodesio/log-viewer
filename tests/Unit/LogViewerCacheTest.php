<?php

use Illuminate\Cache\FileStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;

it('it defaults to the app\'s default cache driver', function ($cacheType, $cacheStoreClass) {
    config(['cache.default' => $cacheType]);

    /** @var Repository $repository */
    $repository = app('log-viewer-cache');

    expect($repository->getStore())->toBeInstanceOf($cacheStoreClass);
})->with([
    ['file', FileStore::class],
    ['redis', RedisStore::class],
]);

it('can provide a different cache driver for the log viewer', function () {
    config(['cache.default' => 'redis']);
    config(['log-viewer.cache_driver' => 'file']);

    /** @var Repository $repository */
    $repository = app('log-viewer-cache');

    expect($repository->getStore())->toBeInstanceOf(FileStore::class);
});
