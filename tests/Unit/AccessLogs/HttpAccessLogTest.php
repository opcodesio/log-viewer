<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\HttpAccessLog;

it('can read an access log line', function () {
    $line = '205.123.147.41 - arunas [18/Apr/2023:05:21:57 +0000] "GET /tag HTTP/1.1" 500 2519 "-" "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"';

    $accessLog = new HttpAccessLog($line);

    expect($accessLog->context['ip'])->toBe('205.123.147.41')
        ->and($accessLog->context['identity'])->toBe('-')
        ->and($accessLog->context['remote_user'])->toBe('arunas')
        ->and($accessLog->datetime->toDateTimeString())->toBe('2023-04-18 05:21:57')
        ->and($accessLog->context['method'])->toBe('GET')
        ->and($accessLog->context['path'])->toBe('/tag')
        ->and($accessLog->context['http_version'])->toBe('HTTP/1.1')
        ->and($accessLog->context['status_code'])->toBe(500)
        ->and($accessLog->context['content_length'])->toBe(2519)
        ->and($accessLog->context['referrer'])->toBe('-')
        ->and($accessLog->context['user_agent'])->toBe('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
});

it('can pass file info when making the access log', function () {
    $line = '205.123.147.41 - arunas [18/Apr/2023:05:21:57 +0000] "GET /tag HTTP/1.1" 500 2519 "-" "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"';
    $file = new LogFile($path = 'access.log', $type = LogFile::TYPE_HTTP_ACCESS);

    $accessLog = new HttpAccessLog($line, $file->identifier, $position = 123);

    expect($accessLog->fileIdentifier)->toBe($file->identifier)
        ->and($accessLog->filePosition)->toBe($position);
});

it('can handle missing values', function () {
    $line = '';

    $accessLog = new HttpAccessLog($line);

    expect($accessLog->context['ip'])->toBe(null)
        ->and($accessLog->context['identity'])->toBe(null)
        ->and($accessLog->context['remote_user'])->toBe(null)
        ->and($accessLog->datetime)->toBe(null)
        ->and($accessLog->context['method'])->toBe(null)
        ->and($accessLog->context['path'])->toBe(null)
        ->and($accessLog->context['http_version'])->toBe(null)
        ->and($accessLog->context['status_code'])->toBe(null)
        ->and($accessLog->context['content_length'])->toBe(null)
        ->and($accessLog->context['referrer'])->toBe(null)
        ->and($accessLog->context['user_agent'])->toBe(null);
});

it('strips empty chars at the end', function ($chars) {
    $line = '205.123.147.41 - arunas [18/Apr/2023:05:21:57 +0000] "GET /tag HTTP/1.1" 500 2519 "-" "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"';

    $accessLog = new HttpAccessLog($line.$chars);

    expect($accessLog->getOriginalText())->toBe($line);
})->with([
    ['chars' => "\n"],
    ['chars' => "\r\n"],
    ['chars' => "\r"],
    ['chars' => '    '],
]);
