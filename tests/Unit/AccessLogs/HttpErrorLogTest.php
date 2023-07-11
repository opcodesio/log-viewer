<?php

use Opcodes\LogViewer\HttpErrorLog;

it('can parse an HTTP error log', function () {
    $line = "[Sun Jul 09 09:08:27.901758 2023] [php:error] [pid 116942] [client 20.253.242.138:50173] script '/var/www/cgi-bin/cloud.php' not found or unable to stat";

    $log = new HttpErrorLog($line);

    expect($log->text)->toBe($line)
        ->and($log->datetime->toDateTimeString())->toBe('2023-07-09 09:08:27')
        ->and($log->module)->toBe('php')
        ->and($log->level)->toBe('error')
        ->and($log->pid)->toBe(116942)
        ->and($log->client)->toBe('20.253.242.138:50173')
        ->and($log->message)->toBe("script '/var/www/cgi-bin/cloud.php' not found or unable to stat");
});

it('can store the related file details', function () {
    $line = "[Sun Jul 09 09:08:27.901758 2023] [php:error] [pid 116942] [client 20.253.242.138:50173] script '/var/www/cgi-bin/cloud.php' not found or unable to stat";

    $log = new HttpErrorLog($line, $fileIdentifier = 'test-xyz.log', $filePosition = 123);

    expect($log->fileIdentifier)->toBe($fileIdentifier)
        ->and($log->filePosition)->toBe($filePosition);
});

it('handles missing details', function () {
    $line = '';

    $log = new HttpErrorLog($line);

    expect($log->text)->toBe($line)
        ->and($log->datetime?->toDateTimeString())->toBe(null)
        ->and($log->module)->toBe(null)
        ->and($log->level)->toBe(null)
        ->and($log->pid)->toBe(null)
        ->and($log->client)->toBe(null)
        ->and($log->message)->toBe(null);
});

it('strips empty chars at the end', function ($chars) {
    $line = "[Sun Jul 09 09:08:27.901758 2023] [php:error] [pid 116942] [client 20.253.242.138:50173] script '/var/www/cgi-bin/cloud.php' not found or unable to stat";

    $accessLog = new HttpErrorLog($line . $chars);

    expect($accessLog->text)->toBe($line);
})->with([
    ['chars' => "\n"],
    ['chars' => "\r\n"],
    ['chars' => "\r"],
    ['chars' => '    '],
]);
