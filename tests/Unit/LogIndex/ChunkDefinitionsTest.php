<?php

use Opcodes\LogViewer\Facades\Cache;
use Opcodes\LogViewer\Utils\GenerateCacheKey;

it('can fetch a chunk definition for an empty chunk', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);

    // by default, the first chunk definition can still be fetched
    $chunkDefinitions = $logIndex->getChunkDefinitions();

    expect($chunkDefinitions)->toHaveCount(1)
        ->and($chunkDefinitions[0])->toBe([
            'index' => 0,
            'size' => 0,
            'earliest_timestamp' => null,
            'latest_timestamp' => null,
            'level_counts' => [],
        ]);
});

it('updates the chunk data when adding an entry', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);

    $logIndex->addToIndex($pos = 1500, $time = now(), $level = 'info');

    expect($logIndex->getChunkDefinition(0))->toBe([
        'index' => 0,
        'size' => 1,
        'earliest_timestamp' => $time->timestamp,
        'latest_timestamp' => $time->timestamp,
        'level_counts' => ['info' => 1],
    ]);
});

it('gets multiple chunks after rotating', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);

    $logIndex->addToIndex($firstPos = 1500, $firstTime = now()->subMinute(), 'info');
    $logIndex->addToIndex($secondPos = 2000, $secondTime = now(), 'info');

    expect($logIndex->getChunkDefinitions())->toHaveCount(2)
        ->and($logIndex->getChunkDefinition(0))->toBe([
            'index' => 0,
            'size' => 2,
            'earliest_timestamp' => $firstTime->timestamp,
            'latest_timestamp' => $secondTime->timestamp,
            'level_counts' => ['info' => 2],
        ])
        ->and($logIndex->getChunkDefinition(1))->toBe([
            'index' => 1,
            'size' => 0,
            'earliest_timestamp' => null,
            'latest_timestamp' => null,
            'level_counts' => [],
        ]);

    // Adding another log entry would add to the latest chunk
    $logIndex->addToIndex($thirdPos = 2600, $thirdTime = now(), 'debug');

    expect($logIndex->getChunkDefinition(1))->toBe([
        'index' => 1,
        'size' => 1,
        'earliest_timestamp' => $thirdTime->timestamp,
        'latest_timestamp' => $thirdTime->timestamp,
        'level_counts' => ['debug' => 1],
    ]);
});

it('can fetch a particular chunk definition', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(1);
    $logIndex->addToIndex(1500, now(), 'info');
    $logIndex->addToIndex(2500, now(), 'info');
    $allChunks = $logIndex->getChunkDefinitions();

    expect($logIndex->getChunkDefinition(0))->toBe($allChunks[0])
        ->and($logIndex->getChunkDefinition(1))->toBe($allChunks[1])
        ->and($logIndex->getChunkDefinition(2))->toBe($allChunks[2])
        ->and($logIndex->getChunkDefinition(6))->toBeNull();
});

it('saves chunks to cache', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);
    $logIndex->addToIndex(1500, now(), 'info');
    $logIndex->addToIndex(2500, now(), 'info');
    $metaCacheKey = GenerateCacheKey::for($logIndex, 'metadata');

    $cachedMetadata = Cache::get($metaCacheKey);

    expect($cachedMetadata)->toHaveKey('chunk_definitions')
        ->and($cachedMetadata['chunk_definitions'])->toBeArray()->toHaveCount(1)
        ->and($cachedMetadata['chunk_definitions'][0])
            ->toBe($logIndex->getChunkDefinition(0));

    // after adding a new log entry, the cache won't be updated until calling the –>save() method.
    $logIndex->addToIndex(3500, now(), 'info');

    expect(Cache::get($metaCacheKey))->toBe($cachedMetadata);   // still the old value

    // after saving, it should be updated:
    $logIndex->save();
    $updatedCachedMetadata = Cache::get($metaCacheKey);

    expect($updatedCachedMetadata)->not->toBe($cachedMetadata)
        ->toHaveKey('current_chunk_definition')
        ->and($updatedCachedMetadata['current_chunk_definition'])
            ->toBe($logIndex->getChunkDefinition(1));
});

it('keeps the chunk definitions after re-instantiating the log index', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);
    $logIndex->addToIndex(1500, now()->subMinute(), 'info');
    $logIndex->addToIndex(2500, now(), 'info');
    $logIndex->addToIndex(3500, now(), 'info');
    expect($logIndex->getChunkDefinitions())->toHaveCount(2);
    $firstChunk = $logIndex->getChunkDefinition(0);
    $secondChunk = $logIndex->getChunkDefinition(1);
    $logIndex->save();

    $logIndex = createLogIndex($logIndex->file);

    expect($logIndex->getChunkDefinitions())->toHaveCount(2)
        ->and($logIndex->getChunkDefinition(0))->toBe($firstChunk)
        ->and($logIndex->getChunkDefinition(1))->toBe($secondChunk);
});
