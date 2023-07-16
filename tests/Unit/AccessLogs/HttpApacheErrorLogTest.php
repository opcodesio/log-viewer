<?php

use Opcodes\LogViewer\HttpApacheErrorLog;

it('can parse an HTTP error log', function () {
    $line = "[Sun Jul 09 09:08:27.901758 2023] [php:error] [pid 116942] [client 20.253.242.138:50173] script '/var/www/cgi-bin/cloud.php' not found or unable to stat";

    $log = new HttpApacheErrorLog($line);

    expect($log->datetime->toDateTimeString())->toBe('2023-07-09 09:08:27')
        ->and($log->level)->toBe('error')
        ->and($log->message)->toBe("script '/var/www/cgi-bin/cloud.php' not found or unable to stat")
        ->and($log->context['module'])->toBe('php')
        ->and($log->context['pid'])->toBe(116942)
        ->and($log->context['client'])->toBe('20.253.242.138:50173');
});

it('can parse an HTTP error log with client part missing', function () {
    $line = "[Sun Jul 09 09:08:27.901758 2023] [php:error] [pid 116942] script '/var/www/cgi-bin/cloud.php' not found or unable to stat";

    $log = new HttpApacheErrorLog($line);

    expect($log->datetime->toDateTimeString())->toBe('2023-07-09 09:08:27')
        ->and($log->level)->toBe('error')
        ->and($log->message)->toBe("script '/var/www/cgi-bin/cloud.php' not found or unable to stat")
        ->and($log->context['module'])->toBe('php')
        ->and($log->context['pid'])->toBe(116942)
        ->and($log->context['client'])->toBe(null);
});

it('can store the related file details', function () {
    $line = "[Sun Jul 09 09:08:27.901758 2023] [php:error] [pid 116942] [client 20.253.242.138:50173] script '/var/www/cgi-bin/cloud.php' not found or unable to stat";

    $log = new HttpApacheErrorLog($line, $fileIdentifier = 'test-xyz.log', $filePosition = 123);

    expect($log->fileIdentifier)->toBe($fileIdentifier)
        ->and($log->filePosition)->toBe($filePosition);
});

it('handles missing details', function () {
    $line = '';

    $log = new HttpApacheErrorLog($line);

    expect($log->datetime)->toBe(null)
        ->and($log->level)->toBe(null)
        ->and($log->message)->toBe(null)
        ->and($log->context['module'])->toBe(null)
        ->and($log->context['pid'])->toBe(null)
        ->and($log->context['client'])->toBe(null);
});

it('strips empty chars at the end', function ($chars) {
    $line = "[Sun Jul 09 09:08:27.901758 2023] [php:error] [pid 116942] [client 20.253.242.138:50173] script '/var/www/cgi-bin/cloud.php' not found or unable to stat";

    $accessLog = new HttpApacheErrorLog($line.$chars);

    expect($accessLog->getOriginalText())->toBe($line);
})->with([
    ['chars' => "\n"],
    ['chars' => "\r\n"],
    ['chars' => "\r"],
    ['chars' => '    '],
]);
