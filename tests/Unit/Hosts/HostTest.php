<?php

use Opcodes\LogViewer\Host;

it('can check if host is remote', function () {
    $host = new Host('local', 'Local', null);

    expect($host->isRemote())->toBeFalse();

    $host = new Host('remote', 'Remote', 'https://example.com/log-viewer');

    expect($host->isRemote())->toBeTrue();
});
