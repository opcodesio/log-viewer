<?php

use Opcodes\LogViewer\LogLevels\LaravelLogLevel;
use Opcodes\LogViewer\Logs\LaravelLog;
use function PHPUnit\Framework\assertEquals;

it('can understand the default Laravel log format', function () {
    $text = '[2022-08-25 11:16:17] local.DEBUG: Example log entry for the level debug';

    $log = new LaravelLog($text, $fileIdentifier = 'laravel.log', $filePosition = 5200, $index = 10);

    assertEquals($index, $log->index);
    assertEquals(LaravelLogLevel::Debug, $log->level);
    assertEquals('local', $log->extra['environment']);
    assertEquals('2022-08-25 11:16:17', $log->datetime->toDateTimeString());
    assertEquals('Example log entry for the level debug', $log->message);
    assertEquals('Example log entry for the level debug', $log->getOriginalText());
    assertEquals($fileIdentifier, $log->fileIdentifier);
    assertEquals($filePosition, $log->filePosition);
});

it('can understand multi-line logs', function () {
    $logText = <<<'EOF'
Example log entry for the level debug
with multiple lines of content.
can contain dumped objects or JSON as well - it's all part of the contents.
EOF;
    $text = '[2022-08-25 11:16:17] local.DEBUG: '.$logText;

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals('Example log entry for the level debug', $log->message);
    assertEquals($logText, $log->getOriginalText());
});

it('extracts JSON from the log text', function () {
    $logText = <<<'EOF'
Example log entry for the level debug
with multiple lines of content.
{"one":1,"two":"two","three":[1,2,3]}
can contain dumped objects or JSON as well - it's all part of the contents.
EOF;
    $jsonString = '{"one":1,"two":"two","three":[1,2,3]}';
    $text = '[2022-08-25 11:16:17] local.DEBUG: '.$logText;

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals('Example log entry for the level debug', $log->message);
    assertEquals(rtrim(str_replace($jsonString, '', $logText)), $log->getOriginalText());
    assertEquals(json_decode($jsonString, true), $log->context);
});

it('can understand the optional microseconds in the timestamp', function () {
    $text = '[2022-08-25 11:16:17.125000] local.DEBUG: Example log entry for the level debug';

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals(125000, $log->datetime->micro);
});

it('can understand the optional time offset in the timestamp', function () {
    $text = '[2022-08-25 11:16:17.125000+02:00] local.DEBUG: Example log entry for the level debug';
    $expectedTimestamp = 1661418977;

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals($expectedTimestamp, $log->datetime->timestamp);

    // Meanwhile, if we switch the time offset an hour forward,
    // we can expect the timestamp to be reduced by 3600 seconds.
    $text = '[2022-08-25 11:16:17.125000+03:00] local.DEBUG: Example log entry for the level debug';
    $newExpectedTimestamp = $expectedTimestamp - 3600;

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals($newExpectedTimestamp, $log->datetime->timestamp);
});

it('can handle text in-between timestamp and environment/severity', function () {
    $text = '[2022-08-25 11:16:17] some additional text [!@#$%^&] and characters // !@#$ local.DEBUG: Example log entry for the level debug';
    $expectedAdditionalText = 'some additional text [!@#$%^&] and characters // !@#$';

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals($expectedAdditionalText.' Example log entry for the level debug', $log->message);
    assertEquals($expectedAdditionalText.' Example log entry for the level debug', $log->getOriginalText());
    // got to make sure the rest of the data is still processed correctly!
    assertEquals('local', $log->extra['environment']);
    assertEquals(LaravelLogLevel::Debug, $log->level);
});

it('finds the correct log level', function ($levelProvided, $levelExpected) {
    $text = "[2022-08-25 11:16:17] local.$levelProvided: Example log entry for the level debug";

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals($levelExpected, $log->level);
})->with([
    ['INFO', LaravelLogLevel::Info],
    ['DEBUG', LaravelLogLevel::Debug],
    ['ERROR', LaravelLogLevel::Error],
    ['WARNING', LaravelLogLevel::Warning],
    ['CRITICAL', LaravelLogLevel::Critical],
    ['ALERT', LaravelLogLevel::Alert],
    ['EMERGENCY', LaravelLogLevel::Emergency],
    ['info', LaravelLogLevel::Info],
    ['iNfO', LaravelLogLevel::Info],
    ['', LaravelLogLevel::None],
]);

it('handles missing message', function () {
    $text = '[2022-11-07 17:51:33] production.ERROR: ';

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals('2022-11-07 17:51:33', $log->datetime?->toDateTimeString());
    assertEquals(LaravelLogLevel::Error, $log->level);
    assertEquals('production', $log->extra['environment']);
    assertEquals('', $log->getOriginalText());
});

it('can set a custom timezone of the log entry', function () {
    $text = '[2022-11-07 17:51:33] production.ERROR: test message';
    config(['log-viewer.timezone' => $tz = 'Europe/Vilnius']);

    $log = new LaravelLog($text, 'laravel.log', 0, 0);

    assertEquals($tz, $log->datetime->timezoneName);
    $expectedTime = \Carbon\Carbon::parse('2022-11-07 17:51:33', 'UTC')->tz($tz)->toDateTimeString();
    assertEquals($expectedTime, $log->datetime->toDateTimeString());
});
