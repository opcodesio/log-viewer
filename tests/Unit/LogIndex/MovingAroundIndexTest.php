<?php

it('can get the next index and file position from an index', function () {
    $logIndex = createLogIndex(null, null, [
        $idx1 = 0 => [$pos1 = 0, now(), 'info'],
        $idx2 = 1 => [$pos2 = 500, now(), 'info'],
        $idx3 = 2 => [$pos3 = 1000, now(), 'info'],
    ]);

    // By default, it starts with the first item
    expect($logIndex->next())->toBe([$idx1, $pos1])
        ->and($logIndex->next())->toBe([$idx2, $pos2])
        ->and($logIndex->next())->toBe([$idx3, $pos3])
        ->and($logIndex->next())->toBeNull();
});

it('can reset the index from the start', function () {
    $logIndex = createLogIndex(null, null, [
        $idx1 = 0 => [$pos1 = 0, now(), 'info'],
        $idx2 = 1 => [$pos2 = 500, now(), 'info'],
        $idx3 = 2 => [$pos3 = 1000, now(), 'info'],
    ]);
    $logIndex->next();
    $logIndex->next();

    expect($logIndex->reset()->next())->toBe([$idx1, $pos1]);
});

it('takes into account severity filters', function () {
    $logIndex = createLogIndex(null, null, [
        $idx1 = 0 => [$pos1 = 0, now(), 'debug'],
        $idx2 = 1 => [$pos2 = 500, now(), 'info'],
        $idx3 = 2 => [$pos3 = 1000, now(), 'debug'],
    ]);
    $logIndex->forLevels('info');

    expect($logIndex->next())->toBe([$idx2, $pos2])
        ->and($logIndex->next())->toBeNull();
});
