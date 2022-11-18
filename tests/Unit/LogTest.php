<?php

use Opcodes\LogViewer\Level;
use Opcodes\LogViewer\Log;
use function PHPUnit\Framework\assertEquals;

it('can understand the default Laravel log format', function () {
    $text = '[2022-08-25 11:16:17] local.DEBUG: Example log entry for the level debug';

    $log = new Log($index = 10, $text, $fileIdentifier = 'laravel.log', $filePosition = 5200);

    assertEquals($index, $log->index);
    assertEquals(Level::Debug, $log->level->value);
    assertEquals('local', $log->environment);
    assertEquals('2022-08-25 11:16:17', $log->time->toDateTimeString());
    assertEquals('Example log entry for the level debug', $log->text);
    assertEquals('Example log entry for the level debug', $log->fullText);
    assertEquals($fileIdentifier, $log->fileIdentifier);
    assertEquals($filePosition, $log->filePosition);
});

it('can understand multi-line logs', function () {
    $logText = <<<'EOF'
Example log entry for the level debug
with multiple lines of content.
{"one":1,"two":"two","three":[1,2,3]}
can contain dumped objects or JSON as well - it's all part of the contents.
EOF;
    $text = '[2022-08-25 11:16:17] local.DEBUG: '.$logText;

    $log = new Log(0, $text, 'laravel.log', 0);

    assertEquals('Example log entry for the level debug', $log->text);
    assertEquals($logText, $log->fullText);
});

it('can understand the optional microseconds in the timestamp', function () {
    $text = '[2022-08-25 11:16:17.125000] local.DEBUG: Example log entry for the level debug';

    $log = new Log(0, $text, 'laravel.log', 0);

    assertEquals(125000, $log->time->micro);
});

it('can understand the optional time offset in the timestamp', function () {
    $text = '[2022-08-25 11:16:17.125000+02:00] local.DEBUG: Example log entry for the level debug';
    $expectedTimestamp = 1661418977;

    $log = new Log(0, $text, 'laravel.log', 0);

    assertEquals($expectedTimestamp, $log->time->timestamp);

    // Meanwhile, if we switch the time offset an hour forward,
    // we can expect the timestamp to be reduced by 3600 seconds.
    $text = '[2022-08-25 11:16:17.125000+03:00] local.DEBUG: Example log entry for the level debug';
    $newExpectedTimestamp = $expectedTimestamp - 3600;

    $log = new Log(0, $text, 'laravel.log', 0);

    assertEquals($newExpectedTimestamp, $log->time->timestamp);
});

it('can handle text in-between timestamp and environment/severity', function () {
    $text = '[2022-08-25 11:16:17] some additional text [] and characters // !@#$ local.DEBUG: Example log entry for the level debug';
    $expectedAdditionalText = 'some additional text [] and characters // !@#$';

    $log = new Log(0, $text, 'laravel.log', 0);

    assertEquals($expectedAdditionalText.' Example log entry for the level debug', $log->text);
    assertEquals($expectedAdditionalText.' Example log entry for the level debug', $log->fullText);
    // got to make sure the rest of the data is still processed correctly!
    assertEquals('local', $log->environment);
    assertEquals(Level::Debug, $log->level->value);
});

it('finds the correct log level', function ($levelProvided, $levelExpected) {
    $text = "[2022-08-25 11:16:17] local.$levelProvided: Example log entry for the level debug";

    $log = new Log(0, $text, 'laravel.log', 0);

    assertEquals($levelExpected, $log->level->value);
})->with([
    ['INFO', Level::Info],
    ['DEBUG', Level::Debug],
    ['ERROR', Level::Error],
    ['WARNING', Level::Warning],
    ['CRITICAL', Level::Critical],
    ['ALERT', Level::Alert],
    ['EMERGENCY', Level::Emergency],
    ['PROCESSING', Level::Processing],
    ['PROCESSED', Level::Processed],
    ['info', Level::Info],
    ['iNfO', Level::Info],
    ['', Level::None],
]);

it('handles missing message', function () {
    $text = '[2022-11-07 17:51:33] production.ERROR: ';

    $log = new Log(0, $text, 'laravel.log', 0);

    assertEquals('2022-11-07 17:51:33', $log->time?->toDateTimeString());
    assertEquals(Level::Error, $log->level->value);
    assertEquals('production', $log->environment);
    assertEquals('', $log->fullText);
});
