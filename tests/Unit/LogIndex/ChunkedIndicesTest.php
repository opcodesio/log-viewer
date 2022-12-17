<?php

use Opcodes\LogViewer\Exceptions\InvalidChunkSizeException;

it('can set the chunk size for the log index', function () {
    $logIndex = createLogIndex();

    $logIndex->setMaxChunkSize(2);

    expect($logIndex->getMaxChunkSize())->toBe(2);
});

it('cannot set a chunk size lower than 1', function () {
    $logIndex = createLogIndex();

    $logIndex->setMaxChunkSize(0);
})->throws(InvalidChunkSizeException::class);

it('increments the current chunk size after adding a log', function () {
    $logIndex = createLogIndex();
    expect($logIndex->getCurrentChunk()->size)->toBe(0);

    $logIndex->addToIndex(1000, now()->subMinute(), 'info');

    expect($logIndex->getCurrentChunk()->size)->toBe(1);
});

it('chunks big indices into smaller pieces', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(1);

    $firstIndexGenerated = $logIndex->addToIndex(
        $firstFilePosition = 1000,
        $firstDate = now()->subMinute(),
        'info'
    );
    $secondIndexGenerated = $logIndex->addToIndex(
        $secondFilePosition = 1500,
        $secondDate = now(),
        'debug',
    );

    expect($logIndex->getChunkData(0))->toBe([
        $firstDate->timestamp => [
            'info' => [
                $firstIndexGenerated => $firstFilePosition,
            ],
        ],
    ])
        ->and($logIndex->getChunkData(1))->toBe([
            $secondDate->timestamp => [
                'debug' => [
                    $secondIndexGenerated => $secondFilePosition,
                ],
            ],
        ]);
});

it('can get the number of chunks generated so far', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);
    // let's generate three logs = 2 chunks so far.
    $logIndex->addToIndex(1000, now()->subMinute(), 'info');
    $logIndex->addToIndex(1500, now(), 'debug');
    $logIndex->addToIndex(2500, now(), 'debug');

    expect($logIndex->getChunkCount())->toBe(2);

    // now let's add another one, which would close off the first 2 chunks and create another one that's empty.
    $logIndex->addToIndex(3000, now(), 'info');

    expect($logIndex->getChunkCount())->toBe(3);
});

it('returns null for a non-existent chunk', function () {
    $logIndex = createLogIndex();

    expect($logIndex->getChunkData(5))->toBeNull();
});

it('remembers the number of chunks after re-instantiation', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(1);
    // 2 log entries at 1 per chunk = 3 chunks in total (2 full + 1 empty)
    $logIndex->addToIndex(1000, now()->subMinute(), 'info');
    $logIndex->addToIndex(1500, now(), 'debug');
    expect($logIndex->getChunkCount())->toBe(3);

    $newInstance = createLogIndex($logIndex->file);

    expect($newInstance->getChunkCount())->toBe(3);
});

it('combines all chunks when getting the full index', function () {
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);
    // let's generate three logs = 2 chunks so far.
    $logIndex->addToIndex($firstPos = 1000, $firstDate = now()->subMinute(), 'info');
    $logIndex->addToIndex($secondPos = 1500, $secondDate = now(), 'debug');
    $logIndex->addToIndex($thirdPos = 2500, $thirdDate = $secondDate, 'debug');
    expect($logIndex->getChunkCount())->toBe(2);

    $fullIndex = $logIndex->get();

    expect($fullIndex)->toBe([
        $firstDate->timestamp => [
            'info' => [
                0 => $firstPos,
            ],
        ],
        $secondDate->timestamp => [
            'debug' => [
                1 => $secondPos,
                2 => $thirdPos,
            ],
        ],
    ]);

    // after saving and re-instantiating the log, we expect it to persist the index
    $logIndex->save();
    $logIndex = createLogIndex($logIndex->file);

    expect($logIndex->get())->toBe([
        $firstDate->timestamp => [
            'info' => [
                0 => $firstPos,
            ],
        ],
        $secondDate->timestamp => [
            'debug' => [
                1 => $secondPos,
                2 => $thirdPos,
            ],
        ],
    ]);
});
