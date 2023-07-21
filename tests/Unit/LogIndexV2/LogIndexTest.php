<?php

it('starts off with an empty index', function () {
    $logIndex = createLogIndexV2();

    expect($logIndex->get())->toBeEmpty();
});

it('can index a log entry', function () {
    $logIndex = createLogIndexV2();

    $logIndex->addToIndex(
        $firstFilePosition = 1000,
        $firstDate = now()->subMinute(),
        $level = 'info'
    );

    expect($logIndex->get())->toBe([
        [
            'idx_from' => 0,
            'idx_to' => 0,
            'pos_from' => $firstFilePosition,
            'pos_to' => $firstFilePosition,
            'ts_from' => $firstDate->timestamp,
            'ts_to' => $firstDate->timestamp,
            'levels' => [
                'info' => 1,
            ],
            'count' => 1,
        ]
    ]);

    $logIndex->addToIndex(
        $secondFilePosition = 1500,
        $secondDate = now(),
        'debug'
    );

    expect($logIndex->get())->toBe([
        [
            'idx_from' => 0,
            'idx_to' => 1,
            'pos_from' => $firstFilePosition,
            'pos_to' => $secondFilePosition,
            'ts_from' => $firstDate->timestamp,
            'ts_to' => $secondDate->timestamp,
            'levels' => [
                'info' => 1,
                'debug' => 1,
            ],
            'count' => 2,
        ],
    ]);
});

it('rotates chunk', function () {
    $logIndex = createLogIndexV2();

    $logIndex->setChunkSize(2);

    $logIndex->addToIndex($p1 = 100, $t1 = now()->subMinutes(5), 'info');
    $logIndex->addToIndex($p2 = 200, $t2 = now()->subMinutes(4), 'info');
    $logIndex->addToIndex($p3 = 300, $t3 = now()->subMinutes(3), 'info');
    $logIndex->addToIndex($p4 = 400, $t4 = now()->subMinutes(2), 'info');

    expect($logIndex->get())->toHaveCount(2);   // 2 chunks

    $logIndex->addToIndex($p5 = 500, $t5 = now()->subMinutes(1), 'info');

    expect($logIndex->get())->toBe([
        [
            'idx_from' => 0,
            'idx_to' => 1,
            'pos_from' => $p1,
            'pos_to' => $p3,
            'ts_from' => $t1->timestamp,
            'ts_to' => $t2->timestamp,
            'levels' => [
                'info' => 2,
            ],
            'count' => 2,
        ],
        [
            'idx_from' => 2,
            'idx_to' => 3,
            'pos_from' => $p3,
            'pos_to' => $p5,
            'ts_from' => $t3->timestamp,
            'ts_to' => $t4->timestamp,
            'levels' => [
                'info' => 2,
            ],
            'count' => 2,
        ],
        [
            'idx_from' => 4,
            'idx_to' => 4,
            'pos_from' => $p5,
            'pos_to' => $p5,
            'ts_from' => $t5->timestamp,
            'ts_to' => $t5->timestamp,
            'levels' => [
                'info' => 1,
            ],
            'count' => 1,
        ],
    ]);
});

it('can save the results to cache', function () {
    $logIndex = createLogIndexV2();
    $logIndex->addToIndex(100, now()->subMinute(), 'info');

    $logIndex->save();

    $logIndex = createLogIndexV2($logIndex->file);
    expect($logIndex->get())->toBe([
        [
            'idx_from' => 0,
            'idx_to' => 0,
            'pos_from' => 100,
            'pos_to' => 100,
            'ts_from' => now()->subMinute()->timestamp,
            'ts_to' => now()->subMinute()->timestamp,
            'levels' => [
                'info' => 1,
            ],
            'count' => 1,
        ]
    ]);
});

it('can calculate the level counts, and total count', function () {
    $logIndex = createLogIndexV2();
    $logIndex->setChunkSize(2); // to make sure it also calculates across chunks

    $logIndex->addToIndex(100, now()->subMinute(), 'info');
    $logIndex->addToIndex(200, now()->subMinute(), 'info');
    $logIndex->addToIndex(300, now()->subMinute(), 'debug');
    $logIndex->addToIndex(400, now()->subMinute(), 'debug');
    $logIndex->addToIndex(500, now()->subMinute(), 'debug');
    $logIndex->addToIndex(600, now()->subMinute(), 'error');
    $logIndex->addToIndex(700, now()->subMinute(), 'error');
    $logIndex->addToIndex(800, now()->subMinute(), 'error');
    $logIndex->addToIndex(900, now()->subMinute(), 'error');

    expect($logIndex->get())->toHaveCount(5)
        ->and($logIndex->getLevelCounts()->all())->toBe([
            'info' => 2,
            'debug' => 3,
            'error' => 4,
        ])
        ->and($logIndex->count())->toBe(9);
});
