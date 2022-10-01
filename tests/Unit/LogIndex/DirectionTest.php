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

test('getFlatArray() results are returned sorted based on direction chosen', function () {
    $logIndex = createLogIndex(null, null, [
        [$posMiddle = 0, $tsMiddle = now()->subHours(2)->timestamp, 'info'],
        [$posEarliest = 100, $tsEarliest = now()->subHours(3)->timestamp, 'info'],
        [$posLatest = 200, $tsLatest = now()->subHours(1)->timestamp, 'info'],
    ]);

    // first, forward
    $forwardResult = $logIndex->forward()->getFlatArray();
    expect($forwardResult)->toBe([
        1 => $posEarliest,
        0 => $posMiddle,
        2 => $posLatest,
    ]);

    // now backwards
    $backwardsResult = $logIndex->backward()->getFlatArray();
    expect($backwardsResult)->toBe([
        2 => $posLatest,
        0 => $posMiddle,
        1 => $posEarliest,
    ]);
});
