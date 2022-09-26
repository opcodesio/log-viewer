<?php

use Illuminate\Support\Facades\Cache;
use Opcodes\LogViewer\LogFile;

it('starts off with an empty index', function () {
    $logIndex = createLogIndex();

    expect($logIndex->get())->toBeEmpty();
});

it('can index a log entry', function () {
    $logIndex = createLogIndex();

    $firstIndexGenerated = $logIndex->addToIndex(
        $firstFilePosition = 1000,
        $firstDate = now()->subMinute(),
        $level = 'info'
    );

    expect($firstIndexGenerated)->toBe(0)
        ->and($logIndex->get())->toBe([
            $firstDate->timestamp => [
                'info' => [
                    $firstIndexGenerated => $firstFilePosition,
                ],
            ],
        ]);

    // Adding another index should give a new generated index, and also add it to the full array
    $secondIndexGenerated = $logIndex->addToIndex(
        $newFilePosition = 1500,
        $secondDate = now(),
        'debug'
    );

    expect($secondIndexGenerated)->toBe(1)
        ->and($logIndex->get())->toBe([
            $firstDate->timestamp => [
                'info' => [
                    $firstIndexGenerated => $firstFilePosition,
                ],
            ],
            $secondDate->timestamp => [
                'debug' => [
                    $secondIndexGenerated => $newFilePosition,
                ],
            ],
        ]);
});

it('can return an index for selected date range', function () {
    $logIndex = createLogIndex();
    $firstIndexGenerated = $logIndex->addToIndex(
        $firstFilePosition = 1000,
        $firstDate = now()->subMinute(),
        $level = 'info'
    );
    $secondIndexGenerated = $logIndex->addToIndex(
        $newFilePosition = 1500,
        $secondDate = now(),
        'debug'
    );

    $index = $logIndex->forDateRange(from: now()->subSeconds(30))->get();

    expect($index)->toBe([
        $secondDate->timestamp => [
            'debug' => [
                $secondIndexGenerated => $newFilePosition,
            ],
        ],
    ]);
});

it('can return an index for selected severity levels', function () {
    $logIndex = createLogIndex();
    $firstIndexGenerated = $logIndex->addToIndex(
        $firstFilePosition = 1000,
        $firstDate = now()->subMinute(),
        $level = 'info'
    );
    $secondIndexGenerated = $logIndex->addToIndex(
        $newFilePosition = 1500,
        $secondDate = now(),
        'debug'
    );

    $index = $logIndex->forLevels(['danger', 'info'])->get();

    expect($index)->toBe([
        $firstDate->timestamp => [
            'info' => [
                $firstIndexGenerated => $firstFilePosition,
            ],
        ],
    ]);
});

it('tries to fetch the index from the cache first', function () {
    $logIndex = createLogIndex();
    $cacheKey = $logIndex->chunkCacheKey(0);
    Cache::put(
        $cacheKey,
        $cachedIndexData = [
            1663249701 => [
                'info' => [
                    0 => 0,
                    1 => 1500,
                ],
                'debug' => [
                    2 => 2500,
                ],
            ],
        ],
        now()->addMinute(),
    );
    // reload the log index instance, so it fetches from the cache.
    $logIndex = createLogIndex($logIndex->getFile());

    expect($logIndex->get())->toBe($cachedIndexData);

    // Make sure to clean up!
    Cache::forget($cacheKey);
});

it('can save to the cache after building up the index', function () {
    $logIndex = createLogIndex();
    $cacheKey = $logIndex->chunkCacheKey(0);    // by default, it will save to the first chunk
    $firstIndexGenerated = $logIndex->addToIndex(
        $firstFilePosition = 1000,
        $firstDate = now()->subMinute(),
        $level = 'info'
    );
    $expectedLogIndexData = [
        $firstDate->timestamp => [
            'info' => [
                $firstIndexGenerated => $firstFilePosition,
            ],
        ],
    ];
    expect($logIndex->get())->toBe($expectedLogIndexData)
        ->and(Cache::get($cacheKey))->toBeNull();

    $logIndex->save();

    expect(Cache::get($cacheKey))->toBe($expectedLogIndexData);
});

it('can check whether the index is incomplete', function () {
    $logIndex = createLogIndex(
        Mockery::mock(new LogFile('test.log', 'test.log'))->allows(['size' => 0])
    );

    expect($logIndex->incomplete())->toBeFalse();

    // if we then provide a file with some data in it (fake file size),
    // the log index should be considered incomplete
    $logIndex = createLogIndex(
        Mockery::mock(new LogFile('test.log', 'test.log'))->allows(['size' => 1000])
    );

    expect($logIndex->incomplete())->toBeTrue();

    // Now let's say there's a cached status of this log file, where the last position
    // that the file was scanned at, was 1000. If the file size is still at 1000,
    // then the index should be considered complete.
    $logIndex = createLogIndex(
        Mockery::mock(new LogFile('test.log', 'test.log'))->allows(['size' => 1000])
    );
    $logIndex->setLastScannedFilePosition(1000);

    expect($logIndex->incomplete())->toBeFalse();
});

it('can continue from where it left off after re-instantiation', function () {
    $logIndex = createLogIndex();
    $logIndex->addToIndex(0, now(), 'info');
    $logIndex->addToIndex(200, now(), 'info');
    $lastKnownIndex = $logIndex->addToIndex(1000, now(), 'info');
    $logIndex->save();

    $logIndex = createLogIndex($logIndex->getFile());

    $newestEntryIndex = $logIndex->addToIndex(2000, now(), 'debug');

    expect($newestEntryIndex)->toBe($lastKnownIndex + 1);
});
