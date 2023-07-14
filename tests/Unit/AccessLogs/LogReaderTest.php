<?php

it('can read access logs with the default LogReader', function () {
    $file = new \Opcodes\LogViewer\LogFile(__DIR__.'/Fixtures/access_dummy.log');
    $file->logs()->scan();

    $logs = $file->logs()->get();

    expect($logs)->toHaveCount(10);

    /** @var \Opcodes\LogViewer\HttpAccessLog $firstLog */
    $firstLog = $logs[0];

    // 205.123.147.41 - - [18/Jan/2023:05:21:57 +0000] "GET /tag HTTP/1.1" 500 2519 "-" "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
    expect($firstLog->index)->toBe(0)
        ->and($firstLog)->toBeInstanceOf(\Opcodes\LogViewer\HttpAccessLog::class)
        ->and($firstLog->ip)->toBe('205.123.147.41')
        ->and($firstLog->datetime->toDateTimeString())->toBe('2023-01-18 05:21:57')
        ->and($firstLog->statusCode)->toBe(500)
        ->and($firstLog->level)->toBe('500')
        ->and($firstLog->method)->toBe('GET')
        ->and($firstLog->path)->toBe('/tag')
        ->and($firstLog->httpVersion)->toBe('HTTP/1.1')
        ->and($firstLog->contentLength)->toBe(2519)
        ->and($firstLog->referrer)->toBe('-')
        ->and($firstLog->userAgent)->toBe('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
});

it('can read nginx error logs with the default LogReader', function () {
    $file = new Opcodes\LogViewer\LogFile(__DIR__.'/Fixtures/errors_nginx_dummy.log');
    $file->logs()->scan();

    $logs = $file->logs()->get();

    expect($logs)->toHaveCount(9);

    /** @var \Opcodes\LogViewer\HttpNginxErrorLog $firstLog */
    $firstLog = $logs[0];

    // 2023/01/04 11:18:33 [alert] 95160#0: *1473 setsockopt(TCP_NODELAY) failed (22: Invalid argument) while keepalive, client: 127.0.0.1, server: 127.0.0.1:80
    expect($firstLog->index)->toBe(0)
        ->and($firstLog)->toBeInstanceOf(\Opcodes\LogViewer\HttpNginxErrorLog::class)
        ->and($firstLog->datetime->toDateTimeString())->toBe('2023-01-04 11:18:33')
        ->and($firstLog->level)->toBe('alert')
        ->and($firstLog->message)->toBe('*1473 setsockopt(TCP_NODELAY) failed (22: Invalid argument) while keepalive')
        ->and($firstLog->client)->toBe('127.0.0.1')
        ->and($firstLog->server)->toBe('127.0.0.1:80');
});

it('can read apache error logs with the default LogReader', function () {
    $file = new Opcodes\LogViewer\LogFile(__DIR__.'/Fixtures/errors_dummy.log');
    $file->logs()->scan();

    $logs = $file->logs()->get();

    expect($logs)->toHaveCount(40);

    /** @var \Opcodes\LogViewer\HttpApacheErrorLog $firstLog */
    $firstLog = $logs[0];

    // [Sun Jul 09 06:10:51.190799 2023] [ssl:error] [pid 44570] AH02032: Hostname test.example.com provided via SNI and hostname system.test provided via HTTP are different
    expect($firstLog->index)->toBe(0)
        ->and($firstLog)->toBeInstanceOf(\Opcodes\LogViewer\HttpApacheErrorLog::class)
        ->and($firstLog->datetime->toDateTimeString())->toBe('2023-07-09 06:10:51')
        ->and($firstLog->pid)->toBe(44570)
        ->and($firstLog->module)->toBe('ssl')
        ->and($firstLog->level)->toBe('error')
        ->and($firstLog->message)->toBe('AH02032: Hostname test.example.com provided via SNI and hostname system.test provided via HTTP are different');
});

it('can get access log level counts', function () {
    $file = new \Opcodes\LogViewer\LogFile(__DIR__.'/Fixtures/access_dummy.log');
    $file->logs()->scan();

    $levels = $file->logs()->getLevelCounts();

    expect($levels['500']->count)->toBe(3)
        ->and($levels['500']->level)->toBeInstanceOf(\Opcodes\LogViewer\StatusCodeLevel::class)
        ->and($levels['200']->count)->toBe(4)
        ->and($levels['404']->count)->toBe(3);
});
