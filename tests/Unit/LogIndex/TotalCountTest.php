<?php

it('can get the total count of logs in the index', function () {
    $logIndex = createLogIndex(null, null, [
        [0, now(), 'info'],
        [0, now(), 'info'],
        [0, now(), 'debug'],
        [0, now(), 'error'],
    ]);

    expect($logIndex->count())->toBe(4);
});

it('can get the total count after severity filter applied', function () {
    $logIndex = createLogIndex(null, null, [
        [0, now(), 'info'],
        [0, now(), 'info'],
        [0, now(), 'debug'],
        [0, now(), 'error'],
    ]);

    expect($logIndex->forLevels('info')->count())->toBe(2)
        ->and($logIndex->forLevels('debug')->count())->toBe(1)
        ->and($logIndex->forLevels('error')->count())->toBe(1)
        ->and($logIndex->forLevels('warning')->count())->toBe(0);
});
