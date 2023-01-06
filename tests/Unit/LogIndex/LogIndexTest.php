<?php

use Opcodes\LogViewer\Facades\Cache;
use Opcodes\LogViewer\Utils\GenerateCacheKey;

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
        $secondFilePosition = 1500,
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
                    $secondIndexGenerated => $secondFilePosition,
                ],
            ],
        ]);
});

it('can optionally provide a specific index', function () {
    $logIndex = createLogIndex();

    $indexGenerated = $logIndex->addToIndex(
        100,
        now()->subMinute(),
        'info',
        $indexProvided = 10
    );

    expect($indexGenerated)->toBe($indexProvided);
});

it('can get a flat index/position array', function () {
    $logIndex = createLogIndex();
    $firstIndexGenerated = $logIndex->addToIndex(
        $firstFilePosition = 1000,
        $firstDate = now()->subMinute(),
        $level = 'info'
    );
    $secondIndexGenerated = $logIndex->addToIndex(
        $secondFilePosition = 1500,
        $secondDate = now(),
        'debug'
    );

    $flatArray = $logIndex->getFlatIndex();

    expect($flatArray)->toBe([
        $firstIndexGenerated => $firstFilePosition,
        $secondIndexGenerated => $secondFilePosition,
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
        $secondFilePosition = 1500,
        $secondDate = now(),
        'debug'
    );

    $index = $logIndex->forDateRange(from: now()->subSeconds(30))->get();

    expect($index)->toBe([
        $secondDate->timestamp => [
            'debug' => [
                $secondIndexGenerated => $secondFilePosition,
            ],
        ],
    ]);

    // let's also check the flat index
    $flatIndex = $logIndex->forDateRange(from: now()->subSeconds(30))->getFlatIndex();

    expect($flatIndex)->toBe([
        $secondIndexGenerated => $secondFilePosition,
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
        $secondFilePosition = 1500,
        $secondDate = now(),
        'debug'
    );

    $index = $logIndex->forLevels(['error', 'info'])->get();

    expect($index)->toBe([
        $firstDate->timestamp => [
            'info' => [
                $firstIndexGenerated => $firstFilePosition,
            ],
        ],
    ]);

    // let's also check the flat index
    $flatIndex = $logIndex->forLevels(['error', 'info'])->getFlatIndex();

    expect($flatIndex)->toBe([
        $firstIndexGenerated => $firstFilePosition,
    ]);
});

it('tries to fetch the index from the cache first', function () {
    $logIndex = createLogIndex(null, null, [
        [0, 123, 'info'],
        [100, 123, 'info'],
        [200, 123, 'info'],
    ]);
    $cacheKey = GenerateCacheKey::for($logIndex, 'chunk:0');
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
    $logIndex = createLogIndex($logIndex->file);

    expect($logIndex->get())->toBe($cachedIndexData);

    // Make sure to clean up!
    Cache::forget($cacheKey);
});

it('can save to the cache after building up the index', function () {
    $logIndex = createLogIndex();
    $cacheKey = GenerateCacheKey::for($logIndex, 'chunk:0');    // by default, it will save to the first chunk
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
    $logFile = generateLogFile('test.log');
    $logIndex = createLogIndex($logFile);
    expect($logIndex->incomplete())->toBeFalse();

    // if we add some data to the file, the log index should be considered incomplete
    file_put_contents($logFile->path, makeLogEntry());
    $logIndex = createLogIndex($logFile);
    expect($logIndex->incomplete())->toBeTrue();

    // if we had manually set the last scanned position to the end of the file, it should be considered complete.
    $logIndex = createLogIndex($logFile);
    $logIndex->setLastScannedFilePosition($logFile->size());
    expect($logIndex->incomplete())->toBeFalse();
});

it('can continue from where it left off after re-instantiation', function () {
    $logIndex = createLogIndex();
    $logIndex->addToIndex(0, now(), 'info');
    $logIndex->addToIndex(200, now(), 'info');
    $lastKnownIndex = $logIndex->addToIndex(1000, now(), 'info');
    $logIndex->save();

    $logIndex = createLogIndex($logIndex->file);

    $newestEntryIndex = $logIndex->addToIndex(2000, now(), 'debug');

    expect($newestEntryIndex)->toBe($lastKnownIndex + 1);
});

it('can get a particular index position even with chunks', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);
    $idx1 = $logIndex->addToIndex($pos1 = 0, now(), 'info');
    $idx2 = $logIndex->addToIndex($pos2 = 200, now(), 'info');
    $idx3 = $logIndex->addToIndex($pos3 = 400, now(), 'info');
    $idx4 = $logIndex->addToIndex($pos4 = 600, now(), 'info');
    $idx5 = $logIndex->addToIndex($pos5 = 600, now(), 'info');

    expect($logIndex->getPositionForIndex($idx4))->toBe($pos4)
        ->and($logIndex->getPositionForIndex($idx1))->toBe($pos1);
});
