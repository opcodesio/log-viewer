<?php

test('it can limit the number of results expected for reduced index usage', function () {
    $logIndex = createLogIndex();
    $timestamp = now()->subDay()->timestamp;
    $idx1 = $logIndex->addToIndex($pos1 = 0, $timestamp, 'info');
    $idx2 = $logIndex->addToIndex($pos2 = 500, $timestamp, 'debug');
    $logIndex->addToIndex($pos3 = 1000, now()->subHours(22), 'info');

    $results = $logIndex->limit(2)->get();

    expect($results)->toBe([
        $timestamp => [
            'info' => [
                $idx1 => $pos1,
            ],
            'debug' => [
                $idx2 => $pos2,
            ],
        ],
    ]);

    // Let's also check the flat map
    $flatIndex = $logIndex->limit(2)->getFlatArray();

    expect($flatIndex)->toBe([
        $idx1 => $pos1,
        $idx2 => $pos2,
    ]);
});

test('it can limit the result set by providing a first parameter to get()', function () {
    $logIndex = createLogIndex();
    $timestamp = now()->subDay()->timestamp;
    $idx1 = $logIndex->addToIndex($pos1 = 0, $timestamp, 'info');
    $idx2 = $logIndex->addToIndex($pos2 = 500, $timestamp, 'debug');
    $logIndex->addToIndex($pos3 = 1000, now()->subHours(22), 'info');

    $result = $logIndex->get(2);

    expect($result)->toBe([
        $timestamp => [
            'info' => [
                $idx1 => $pos1,
            ],
            'debug' => [
                $idx2 => $pos2,
            ],
        ],
    ])->and($logIndex->getLimit())->toBeNull();

    // Let's also check the flat map
    $flatIndex = $logIndex->getFlatArray(2);

    expect($flatIndex)->toBe([
        $idx1 => $pos1,
        $idx2 => $pos2,
    ])->and($logIndex->getLimit())->toBeNull();
});
