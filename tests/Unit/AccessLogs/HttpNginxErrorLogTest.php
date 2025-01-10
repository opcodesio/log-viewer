<?php

use Opcodes\LogViewer\Logs\HttpNginxErrorLog;

it('can parse a full Nginx error log entry', function () {
    $line = '2019/07/11 07:19:30 [error] 934#934: *18897816 open() "/local/nginx/static/ads.txt" failed (2: No such file or directory), client: 85.195.82.90, server: app.digitale-sammlungen.de, request: "GET /ads.txt HTTP/1.1", host: "app.digitale-sammlungen.de"';

    $log = new HttpNginxErrorLog($line);

    expect($log->datetime->toDateTimeString())->toBe('2019-07-11 07:19:30')
        ->and($log->level)->toBe('error')
        ->and($log->message)->toBe('*18897816 open() "/local/nginx/static/ads.txt" failed (2: No such file or directory)')
        ->and($log->context['client'])->toBe('85.195.82.90')
        ->and($log->context['server'])->toBe('app.digitale-sammlungen.de')
        ->and($log->context['request'])->toBe('GET /ads.txt HTTP/1.1')
        ->and($log->context['host'])->toBe('app.digitale-sammlungen.de');
});

it('can parse a less complex Nginx error log entry', function () {
    $line = '2023/01/04 11:18:33 [alert] 95160#0: *1473 setsockopt(TCP_NODELAY) failed (22: Invalid argument) while keepalive, client: 127.0.0.1, server: 127.0.0.1:80';

    $log = new HttpNginxErrorLog($line);

    expect($log->datetime->toDateTimeString())->toBe('2023-01-04 11:18:33')
        ->and($log->level)->toBe('alert')
        ->and($log->message)->toBe('*1473 setsockopt(TCP_NODELAY) failed (22: Invalid argument) while keepalive')
        ->and($log->context['client'])->toBe('127.0.0.1')
        ->and($log->context['server'])->toBe('127.0.0.1:80')
        ->and($log->context['request'])->toBe(null)
        ->and($log->context['host'])->toBe(null);
});

it('can parse a minimal log entry', function () {
    $line = '2023/07/01 22:05:03 [warn] 21925#0: the "listen ... http2" directive is deprecated, use the "http2" directive instead in /Users/test/.config/valet/Nginx/blog9.test:9';

    $log = new HttpNginxErrorLog($line);

    expect($log->datetime->toDateTimeString())->toBe('2023-07-01 22:05:03')
        ->and($log->level)->toBe('warn')
        ->and($log->message)->toBe('the "listen ... http2" directive is deprecated, use the "http2" directive instead in /Users/test/.config/valet/Nginx/blog9.test:9')
        ->and($log->context['client'])->toBe(null)
        ->and($log->context['server'])->toBe(null)
        ->and($log->context['request'])->toBe(null)
        ->and($log->context['host'])->toBe(null);
});

it('can parse multiline nginx log entries', function () {
    $file = new \Opcodes\LogViewer\LogFile(__DIR__.'/Fixtures/multiline_nginx_error_dummy.log');
    $file->logs()->scan();

    $logs = $file->logs()->get();

    expect($logs)->toHaveCount(2);

    /** @var HttpNginxErrorLog $firstLog */
    $firstLog = $logs[0];

    // 2024/08/21 09:08:18 [error] 2761052#2761052: *84719 upstream sent too big header while reading response header from upstream,
    // client: 123.123.123.123, server: xxx, request: "GET /api/xx/yy/zz?lang=de HTTP/2.0",
    // upstream: "fastcgi://unix:/var/run/php/php8.1-fpm.sock:", host: "xxx", referrer: "http://some-ip:3000/"
    expect($firstLog->index)->toBe(0)
        ->and($firstLog)->toBeInstanceOf(HttpNginxErrorLog::class)
        ->and($firstLog->datetime->toDateTimeString())->toBe('2024-08-21 09:08:18')
        ->and($firstLog->level)->toBe('error')
        ->and($firstLog->message)->toBe('*84719 upstream sent too big header while reading response header from upstream')
        ->and($firstLog->context['client'])->toBe('123.123.123.123')
        ->and($firstLog->context['server'])->toBe('xxx')
        ->and($firstLog->context['request'])->toBe('GET /api/xx/yy/zz?lang=de HTTP/2.0')
        ->and($firstLog->context['upstream'])->toBe('fastcgi://unix:/var/run/php/php8.1-fpm.sock:')
        ->and($firstLog->context['host'])->toBe('xxx')
        ->and($firstLog->context['referrer'])->toBe('http://some-ip:3000/');

    $secondLog = $logs[1];

    expect($secondLog->index)->toBe(1)
        ->and($secondLog)->toBeInstanceOf(HttpNginxErrorLog::class)
        ->and($secondLog->datetime->toDateTimeString())->toBe('2024-08-21 09:08:19')
        ->and($secondLog->level)->toBe('error')
        ->and($secondLog->message)->toBe(<<<'EOF'
*84719 FastCGI sent in stderr: "PHP message: [2024-08-21 11:08:18] develop.DEBUG: ActivityService: some message
PHP message: [2024-08-21 11:08:18] develop.DEBUG: ActivityService: another message
PHP message: [2024-08-21 11:08:18] develop.DEBUG: ActivityService: blabla:  [{"id":308363,"lat":"xx","lng":"yy"}]
PHP message: [2024-08-21 11:08:18] develop.INFO: provider Payload:  {"attr1":"t","attr2":14400,"sources":[{"id":"source","lat":xx,"lng":yy,"tm":{"t":{"maxT":2},"c":{"":""}}}],"t":[{"id":308363,"lat":"xx","lng":"yy"}],"g_s_client":false,"reversed":false,"polygon":{"id":4326},"pathSerializer":"g"}
PHP message: [2024-08-21 11:08:18] develop.DEBUG: Sending request to final Url (V1) https://someurl/path/v1/endpoint?param=***
EOF);

});
