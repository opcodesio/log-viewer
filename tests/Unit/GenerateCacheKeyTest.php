<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Utils\GenerateCacheKey;

it('can generate a cache key for a LogFile', function () {
    $file = new LogFile('test.log');

    $result = GenerateCacheKey::for($file);

    expect($result)->toBe(
        'lv:'.LogViewer::version().':file:'.$file->identifier
    );
});

it('can configure the cache key prefix', function () {
    config(['log-viewer.cache_key_prefix' => $customPrefix = 'lvCustom']);

    $file = new LogFile('test.log');

    $result = GenerateCacheKey::for($file);

    expect($result)->toBe(
        $customPrefix.':'.LogViewer::version().':file:'.$file->identifier
    );
});

it('can pass a namespace for a more specific cache key', function () {
    $file = new LogFile('test.log');

    $result = GenerateCacheKey::for($file, $namespace = 'randomNamespace');

    expect($result)->toBe(
        GenerateCacheKey::for($file).':'.$namespace
    );
});

it('can generate a cache key for a LogIndex', function () {
    $logIndex = createLogIndex();

    $result = GenerateCacheKey::for($logIndex);

    expect($result)->toBe(
        GenerateCacheKey::for($logIndex->file).':'.$logIndex->identifier
    );
});

it('can generate a cache key for an arbitrary string', function () {
    $string = 'random_string';

    $result = GenerateCacheKey::for($string);

    expect($result)->toBe('lv:'.LogViewer::version().':'.$string);
});
