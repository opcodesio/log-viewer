<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Logs\BaseLog;
use Opcodes\LogViewer\Logs\HttpAccessLog;
use Opcodes\LogViewer\LogTypeRegistrar;
use Opcodes\LogViewer\Tests\Unit\CustomLogs\CustomAccessLog;

beforeEach(function () {
    $this->logRegistrar = app(LogTypeRegistrar::class);
});

it('can extend with another log format', function () {
    LogViewer::extend('custom_log', CustomAccessLog::class);

    expect($this->logRegistrar->getClass('custom_log'))->toBe(CustomAccessLog::class);
});

it('cannot extend with a non-BaseLog class', function () {
    LogViewer::extend('custom_log', stdClass::class);
})->throws(InvalidArgumentException::class);

it('cannot extend with a non-existent class', function () {
    LogViewer::extend('custom_log', 'NonExistentClass');
})->throws(InvalidArgumentException::class);

it('overrides an existing class with the same type', function () {
    expect($this->logRegistrar->getClass('laravel'))->toBe(\Opcodes\LogViewer\Logs\LaravelLog::class);

    LogViewer::extend('laravel', CustomAccessLog::class);

    expect($this->logRegistrar->getClass('laravel'))->toBe(CustomAccessLog::class);
});

it('can guess the type from the provided first line', function ($line, $actualType) {
    expect($this->logRegistrar->guessTypeFromFirstLine($line))
        ->toBe($actualType);
})->with([
    ['line' => '[2021-01-01 00:00:00] laravel.INFO: Test log message', 'type' => 'laravel'],
    ['line' => '8.68.121.11 - - [01/Feb/2023:01:53:51 +0000] "POST /main/tag/category HTTP/2.0" 404 4819 "-" "-"', 'type' => 'http_access'],
    ['line' => '[Sun Jul 09 06:21:31.657578 2023] [ssl:error] [pid 44651] AH02032: Hostname test.example.com provided via SNI and hostname system.test provided via HTTP are different', 'type' => 'http_error_apache'],
    ['line' => '2023/01/04 11:18:33 [alert] 95160#0: *1473 setsockopt(TCP_NODELAY) failed (22: Invalid argument) while keepalive, client: 127.0.0.1, server: 127.0.0.1:80', 'type' => 'http_error_nginx'],
]);

it('prefers user-defined log types over default ones', function () {
    // first, the default http access log
    $defaultAccessLogLine = '8.68.121.11 - UID 123 - [01/Feb/2023:01:53:51 +0000] "POST /main/tag/category HTTP/2.0" 404 4819 "-" "-"';

    expect($this->logRegistrar->guessTypeFromFirstLine($defaultAccessLogLine))
        ->toBe('http_access');

    // now, let's extend with a custom user-defined log type that can also process this same line
    CustomAccessLog::setRegex(HttpAccessLog::$regex);
    LogViewer::extend('http_access_custom', CustomAccessLog::class);

    expect($this->logRegistrar->guessTypeFromFirstLine($defaultAccessLogLine))
        ->toBe('http_access_custom');
});
