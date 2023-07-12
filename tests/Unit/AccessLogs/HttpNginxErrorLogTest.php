<?php

use Opcodes\LogViewer\HttpNginxErrorLog;

it('can parse a full Nginx error log entry', function () {
    $line = '2019/07/11 07:19:30 [error] 934#934: *18897816 open() "/local/nginx/static/ads.txt" failed (2: No such file or directory), client: 85.195.82.90, server: app.digitale-sammlungen.de, request: "GET /ads.txt HTTP/1.1", host: "app.digitale-sammlungen.de"';

    $log = new HttpNginxErrorLog($line);

    expect($log->text)->toBe($line)
        ->and($log->datetime->toDateTimeString())->toBe('2019-07-11 07:19:30')
        ->and($log->level)->toBe('error')
        ->and($log->message)->toBe('*18897816 open() "/local/nginx/static/ads.txt" failed (2: No such file or directory)')
        ->and($log->client)->toBe('85.195.82.90')
        ->and($log->server)->toBe('app.digitale-sammlungen.de')
        ->and($log->request)->toBe('GET /ads.txt HTTP/1.1')
        ->and($log->host)->toBe('app.digitale-sammlungen.de');
});

it('can parse a less complex Nginx error log entry', function () {
    $line = '2023/01/04 11:18:33 [alert] 95160#0: *1473 setsockopt(TCP_NODELAY) failed (22: Invalid argument) while keepalive, client: 127.0.0.1, server: 127.0.0.1:80';

    $log = new HttpNginxErrorLog($line);

    expect($log->text)->toBe($line)
        ->and($log->datetime->toDateTimeString())->toBe('2023-01-04 11:18:33')
        ->and($log->level)->toBe('alert')
        ->and($log->message)->toBe('*1473 setsockopt(TCP_NODELAY) failed (22: Invalid argument) while keepalive')
        ->and($log->client)->toBe('127.0.0.1')
        ->and($log->server)->toBe('127.0.0.1:80')
        ->and($log->request)->toBe(null)
        ->and($log->host)->toBe(null);
});

it('can parse a minimal log entry', function () {
    $line = '2023/07/01 22:05:03 [warn] 21925#0: the "listen ... http2" directive is deprecated, use the "http2" directive instead in /Users/test/.config/valet/Nginx/blog9.test:9';

    $log = new HttpNginxErrorLog($line);

    expect($log->text)->toBe($line)
        ->and($log->datetime->toDateTimeString())->toBe('2023-07-01 22:05:03')
        ->and($log->level)->toBe('warn')
        ->and($log->message)->toBe('the "listen ... http2" directive is deprecated, use the "http2" directive instead in /Users/test/.config/valet/Nginx/blog9.test:9')
        ->and($log->client)->toBe(null)
        ->and($log->server)->toBe(null)
        ->and($log->request)->toBe(null)
        ->and($log->host)->toBe(null);
});
