<?php

use Opcodes\LogViewer\Logs\LaravelLog;

use function PHPUnit\Framework\assertEquals;

it('can set a custom timezone of the log entry', function () {
    $text = '[2022-11-07 17:51:33] production.ERROR: test message';
    config(['log-viewer.timezone' => $tz = 'Europe/Vilnius']);

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals($tz, $log->datetime->timezoneName);
    $expectedTime = \Carbon\Carbon::parse('2022-11-07 17:51:33', 'UTC')->setTimezone($tz)->toDateTimeString();
    assertEquals($expectedTime, $log->datetime->toDateTimeString());
});
