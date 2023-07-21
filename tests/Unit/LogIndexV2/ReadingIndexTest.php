<?php

beforeEach(function () {
    $this->logIndex = createLogIndexV2();
    $this->logIndex->setChunkSize(2); // to make sure it also calculates across chunks

    $this->logIndex->addToIndex(100, 1, 'info');
    $this->logIndex->addToIndex(200, 2, 'info');

    $this->logIndex->addToIndex(300, 3, 'debug');
    $this->logIndex->addToIndex(400, 4, 'debug');

    $this->logIndex->addToIndex(500, 5, 'debug');
    $this->logIndex->addToIndex(600, 6, 'error');

    $this->logIndex->addToIndex(700, 7, 'error');
    $this->logIndex->addToIndex(800, 8, 'error');

    $this->logIndex->addToIndex(900, 9, 'error');
});

it('can get the next group to begin scanning', function () {
    expect($this->logIndex->nextGroup())
        ->toBe([...$this->logIndex->get(0), 'skipped_entries' => 0]);
});

it('can get a specific group index', function () {
    $allGroups = $this->logIndex->get();

    foreach ($allGroups as $index => $group) {
        expect($this->logIndex->get($index))->toBe($group);
    }

    expect($this->logIndex->get(999))->toBe([]);
});

it('can skip a number of entries', function () {
    $secondGroup = [...$this->logIndex->get(1), 'skipped_entries' => 2];

    expect($this->logIndex->skip(2)->nextGroup())->toBe($secondGroup)
        ->and($this->logIndex->skip(3)->nextGroup())->toBe($secondGroup);

    $thirdGroup = [...$this->logIndex->get(2), 'skipped_entries' => 4];

    expect($this->logIndex->skip(4)->nextGroup())->toBe($thirdGroup)
        ->and($this->logIndex->skip(5)->nextGroup())->toBe($thirdGroup);
});

it('can filter out specific levels', function () {
    $secondGroup = [...$this->logIndex->get(1), 'skipped_entries' => 0];

    $this->logIndex->exceptLevels('info');

    expect($this->logIndex->nextGroup())->toBe($secondGroup)
        ->and($this->logIndex->skip(1)->nextGroup())->toBe($secondGroup);

    $thirdGroup = [...$this->logIndex->get(2), 'skipped_entries' => 2];

    expect($this->logIndex->skip(2)->nextGroup())->toBe($thirdGroup)
        ->and($this->logIndex->skip(3)->nextGroup())->toBe($thirdGroup);
});

it('returns empty array when there are no more groups', function () {
    $this->logIndex->skip(9);

    expect($this->logIndex->nextGroup())->toBeEmpty();

    $this->logIndex->exceptLevels('error');

    expect($this->logIndex->skip(5)->nextGroup())->toBeEmpty();
});

it('can filter by dates', function () {
    $this->logIndex->forDateRange(3, 5);    // should skip the first group, and half of fourth.

    $secondGroup = [...$this->logIndex->get(1), 'skipped_entries' => 0];

    expect($this->logIndex->nextGroup())->toBe($secondGroup)
        ->and($this->logIndex->skip(1)->nextGroup())->toBe($secondGroup);

    $thirdGroup = [...$this->logIndex->get(2), 'skipped_entries' => 2];

    expect($this->logIndex->skip(2)->nextGroup())->toBe($thirdGroup)
        ->and($this->logIndex->skip(3)->nextGroup())->toBe($thirdGroup);
});

it('can correctly calculate the skipped_entries when filtering both by dates and levels', function () {
    $logIndex = createLogIndexV2();
    $logIndex->setChunkSize(2);

    $logIndex->addToIndex(100, 1, 'info');
    $logIndex->addToIndex(200, 2, 'error');

    $logIndex->addToIndex(300, 3, 'debug');
    $logIndex->addToIndex(400, 4, 'error');

    $logIndex->addToIndex(500, 5, 'info');
    $logIndex->addToIndex(600, 6, 'error');

    $logIndex->addToIndex(700, 7, 'info');
    $logIndex->addToIndex(800, 8, 'info');

    $logIndex->addToIndex(900, 9, 'debug');

    // let's get all the info logs between 3 and 9 timestamps
    $logIndex->forDateRange(3, 9)->exceptLevels(['debug', 'error']);

    expect($logIndex->nextGroup())->toBe([...$logIndex->get(2), 'skipped_entries' => 0]);

    $logIndex->skip(2);

    expect($logIndex->nextGroup())->toBe([...$logIndex->get(3), 'skipped_entries' => 1]);
});

it('can read the groups in reverse', function () {
    $this->logIndex->reverse();

    expect($this->logIndex->nextGroup())->toBe([...$this->logIndex->get(4), 'skipped_entries' => 0]);

    $this->logIndex->skip(1);

    expect($this->logIndex->nextGroup())->toBe([...$this->logIndex->get(3), 'skipped_entries' => 1]);
});

it('can skip levels backwards as well', function () {
    $this->logIndex->reverse()->exceptLevels('error');

    expect($this->logIndex->nextGroup())->toBe([...$this->logIndex->get(2), 'skipped_entries' => 0]);

    $this->logIndex->skip(1);

    expect($this->logIndex->nextGroup())->toBe([...$this->logIndex->get(1), 'skipped_entries' => 1]);
});
