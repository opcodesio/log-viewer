<?php

use Opcodes\LogViewer\Facades\Cache;

it('can skip a number of entries in the index', function () {
    $timestamp = now()->subHour()->timestamp;
    $logIndex = createLogIndex(null, null, [
        $idx1 = 0 => [$pos1 = 0, $timestamp, 'info'],
        $idx2 = 1 => [$pos2 = 500, $timestamp, 'info'],
        $idx3 = 2 => [$pos3 = 1000, $timestamp, 'info'],
    ]);

    expect($logIndex->skip(2)->get())->toBe([
        $timestamp => ['info' => [2 => $pos3]],
    ])->and($logIndex->skip(2)->getFlatIndex())->toBe([
        2 => $pos3,
    ]);
});

it('can skip a number of entries with severity filter applied', function () {
    $timestamp = now()->subHour()->timestamp;
    $logIndex = createLogIndex(null, null, [
        $idx1 = 0 => [$pos1 = 0, $timestamp, 'info'],
        $idx2 = 1 => [$pos2 = 500, $timestamp, 'debug'],
        $idx3 = 2 => [$pos3 = 1000, $timestamp, 'debug'],
        $idx4 = 3 => [$pos4 = 1000, $timestamp, 'info'],
        $idx5 = 4 => [$pos5 = 1000, $timestamp, 'info'],
    ]);

    // this should now only return the last entry, because there's 3 info logs in total, and we're skipping two.
    $logIndex->forLevels('info')->skip(2);

    expect($logIndex->get())->toBe([
        $timestamp => ['info' => [$idx5 => $pos5]],
    ])->and($logIndex->getFlatIndex())->toBe([
        $idx5 => $pos5,
    ]);
});

it('works across multiple chunks', function () {
    $timestamp = now()->subHour()->timestamp;
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);
    $idx1 = $logIndex->addToIndex($pos1 = 0, $timestamp, 'info');
    $idx2 = $logIndex->addToIndex($pos2 = 500, $timestamp, 'info');
    $idx3 = $logIndex->addToIndex($pos3 = 750, $timestamp, 'debug');
    $idx4 = $logIndex->addToIndex($pos4 = 1100, $timestamp, 'debug');
    $idx5 = $logIndex->addToIndex($pos5 = 1360, $timestamp, 'info');

    // this should cause the second and fifth logs to be returned
    $logIndex->forLevels('info')->skip(1);

    expect($logIndex->get())->toBe([
        $timestamp => ['info' => [
            $idx2 => $pos2,
            $idx5 => $pos5,
        ]],
    ])->and($logIndex->getFlatIndex())->toBe([
        $idx2 => $pos2,
        $idx5 => $pos5,
    ]);
});

test('get() skips unnecessary chunks by not loading them into memory', function () {
    $timestamp = now()->subHour()->timestamp;
    $logIndex = Mockery::mock('Opcodes\LogViewer\LogIndex[getChunk]', [
        new \Opcodes\LogViewer\LogFile('laravel.log', 'laravel.log'),
    ])->makePartial();
    $logIndex->setMaxChunkSize(2);
    $idx1 = $logIndex->addToIndex($pos1 = 0, $timestamp, 'info');
    $idx2 = $logIndex->addToIndex($pos2 = 500, $timestamp, 'info');
    $idx3 = $logIndex->addToIndex($pos3 = 750, $timestamp, 'debug');
    $idx4 = $logIndex->addToIndex($pos4 = 1100, $timestamp, 'debug');
    $idx5 = $logIndex->addToIndex($pos5 = 1360, $timestamp, 'info');
    expect($logIndex->getChunkCount())->toBe(3);

    // now that we have 3 chunks with 2 items each, so if we want to skip 2 items,
    // we can expect that the first chunk wouldn't even be loaded.

    $logIndex->skip(2);

    $logIndex->shouldNotReceive('getChunk')->with(0)
        ->shouldReceive('getChunk')->with(1)->andReturn(Cache::get($logIndex->chunkCacheKey(1)))
        ->shouldReceive('getChunk')->with(2)->andReturn($logIndex->getCurrentChunk()->data);

    expect($logIndex->get())->toBe([
        $timestamp => [
            'debug' => [$idx3 => $pos3, $idx4 => $pos4],
            'info' => [$idx5 => $pos5],
        ],
    ]);
});

test('getFlatIndex() skips unnecessary chunks by not loading them into memory', function () {
    $timestamp = now()->subHour()->timestamp;
    $logIndex = Mockery::mock('Opcodes\LogViewer\LogIndex[getChunk]', [
        new \Opcodes\LogViewer\LogFile('laravel.log', 'laravel.log'),
    ])->makePartial();
    $logIndex->setMaxChunkSize(2);
    $idx1 = $logIndex->addToIndex($pos1 = 0, $timestamp, 'info');
    $idx2 = $logIndex->addToIndex($pos2 = 500, $timestamp, 'info');
    $idx3 = $logIndex->addToIndex($pos3 = 750, $timestamp, 'debug');
    $idx4 = $logIndex->addToIndex($pos4 = 1100, $timestamp, 'debug');
    $idx5 = $logIndex->addToIndex($pos5 = 1360, $timestamp, 'info');
    expect($logIndex->getChunkCount())->toBe(3);

    // now that we have 3 chunks with 2 items each, so if we want to skip 2 items,
    // we can expect that the first chunk wouldn't even be loaded.

    $logIndex->skip(2);

    $logIndex->shouldNotReceive('getChunk')->with(0)
        ->shouldReceive('getChunk')->with(1)->andReturn(Cache::get($logIndex->chunkCacheKey(1)))
        ->shouldReceive('getChunk')->with(2)->andReturn($logIndex->getCurrentChunk()->data);

    expect($logIndex->getFlatIndex())->toBe([
        $idx3 => $pos3,
        $idx4 => $pos4,
        $idx5 => $pos5,
    ]);
});
