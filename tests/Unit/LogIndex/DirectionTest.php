<?php

test('direction is forward by default', function () {
    $logIndex = createLogIndex();

    expect($logIndex->isForward())->toBeTrue()
        ->and($logIndex->isBackward())->toBeFalse();
});

test('direction can be reversed', function () {
    $logIndex = createLogIndex();

    $logIndex->backward();

    expect($logIndex->isForward())->toBeFalse()
        ->and($logIndex->isBackward())->toBeTrue();
});

test('direction can be set to forward again', function () {
    $logIndex = createLogIndex();
    $logIndex->backward();
    expect($logIndex->isBackward())->toBeTrue();

    $logIndex->forward();

    expect($logIndex->isBackward())->toBeFalse()
        ->and($logIndex->isForward())->toBeTrue();
});

test('get() results are returned sorted based on direction chosen', function () {
    $logIndex = createLogIndex(null, null, [
        [$posMiddle = 0, $tsMiddle = now()->subHours(2)->timestamp, 'info'],
        [$posEarliest = 100, $tsEarliest = now()->subHours(3)->timestamp, 'info'],
        [$posLatest = 200, $tsLatest = now()->subHours(1)->timestamp, 'info'],
    ]);

    // first, forward
    $forwardResult = $logIndex->forward()->get();
    expect($forwardResult)->toBe([
        $tsEarliest => ['info' => [1 => $posEarliest]],
        $tsMiddle => ['info' => [0 => $posMiddle]],
        $tsLatest => ['info' => [2 => $posLatest]],
    ]);

    // now backwards
    $backwardsResult = $logIndex->backward()->get();
    expect($backwardsResult)->toBe([
        $tsLatest => ['info' => [2 => $posLatest]],
        $tsMiddle => ['info' => [0 => $posMiddle]],
        $tsEarliest => ['info' => [1 => $posEarliest]],
    ]);
});

test('getFlatIndex() results are returned sorted based on direction chosen', function () {
    $logIndex = createLogIndex(null, null, [
        [$posMiddle = 0, $tsMiddle = now()->subHours(2)->timestamp, 'info'],
        [$posEarliest = 100, $tsEarliest = now()->subHours(3)->timestamp, 'info'],
        [$posLatest = 200, $tsLatest = now()->subHours(1)->timestamp, 'info'],
    ]);

    // first, forward
    $forwardResult = $logIndex->forward()->getFlatIndex();
    expect($forwardResult)->toBe([
        1 => $posEarliest,
        0 => $posMiddle,
        2 => $posLatest,
    ]);

    // now backwards
    $backwardsResult = $logIndex->backward()->getFlatIndex();
    expect($backwardsResult)->toBe([
        2 => $posLatest,
        0 => $posMiddle,
        1 => $posEarliest,
    ]);
});

test('chunked indices are correctly read when going backwards', function () {
    $timestamp = now()->timestamp;
    $logIndex = createLogIndex();
    $logIndex->setMaxChunkSize(2);
    $idx1 = $logIndex->addToIndex($pos1 = 0, $timestamp, 'info');
    $idx2 = $logIndex->addToIndex($pos2 = 100, $timestamp, 'info');
    $idx3 = $logIndex->addToIndex($pos3 = 200, $timestamp, 'info');
    $idx4 = $logIndex->addToIndex($pos4 = 300, $timestamp, 'info');
    $idx5 = $logIndex->addToIndex($pos5 = 400, $timestamp, 'info');
    expect($logIndex->getChunkCount())->toBe(3);

    $logIndex->reverse();

    // we now expect it to read chunks backwards - latest to earliest.
    expect($logIndex->get(2))->toBe([
        $timestamp => [
            'info' => [
                $idx5 => $pos5,
                $idx4 => $pos4,
            ]
        ]
    ]);

    // and the same for flat arrays
    $logIndex->reset();

    expect($logIndex->getFlatIndex(2))->toBe([
        $idx5 => $pos5,
        $idx4 => $pos4,
    ]);
});
