<?php

it('can get the total count of logs in the index', function () {
    $logIndex = createLogIndex(null, null, [
        [0, now(), 'info'],
        [0, now(), 'info'],
        [0, now(), 'debug'],
        [0, now(), 'error'],
    ]);

    expect($logIndex->total())->toBe(4);
});

it('can get the total count after severity filter applied', function () {
    $logIndex = createLogIndex(null, null, [
        [0, now(), 'info'],
        [0, now(), 'info'],
        [0, now(), 'debug'],
        [0, now(), 'error'],
    ]);

    expect($logIndex->forLevels('info')->total())->toBe(2)
        ->and($logIndex->forLevels('debug')->total())->toBe(1)
        ->and($logIndex->forLevels('error')->total())->toBe(1)
        ->and($logIndex->forLevels('warning')->total())->toBe(0);
});
