<?php

use Opcodes\LogViewer\LogLevels\LaravelLogLevel;
use Opcodes\LogViewer\Logs\LaravelLog;
use Opcodes\LogViewer\Utils\Utils;

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
    config(['log-viewer.strip_extracted_context' => true]);
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

it('extracts JSON, but does not remove from the log text if the config is set to false', function () {
    config(['log-viewer.strip_extracted_context' => false]);
    $logText = <<<'EOF'
Example log entry for the level debug
with multiple lines of content.
 {"one":1,"two":"two","three":[1,2,3]}
can contain dumped objects or JSON as well - it's all part of the contents.
EOF;
    $text = '[2022-08-25 11:16:17] local.DEBUG: '.$logText;

    $log = new LaravelLog($text, 'laravel.log');

    assertEquals('Example log entry for the level debug', $log->message);
    assertEquals($logText, $log->getOriginalText());
    assertEquals(json_decode('{"one":1,"two":"two","three":[1,2,3]}', true), $log->context);
});

it('extracts JSON from a complex log', function () {
    config(['log-viewer.strip_extracted_context' => true]);
    $logText = <<<'EOF'
Initiating facebook login.
[HTTP request]
*User ID:*  guest
*Request:*  GET  https://system.test/book/arunas/submit-facebook
*Agent:*    Mozilla/5.0 (iPhone; CPU iPhone OS 15_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/19G82 [FBAN/FBIOS;FBDV/iPhone9,3;FBMD/iPhone;FBSN/iOS;FBSV/15.6.1;FBSS/2;FBID/phone;FBLC/da_DK;FBOP/5]
 {"permalink":"arunas","session":{"_token":"BpqyiNyinnLamzer4jqzrh9NTyC6emFR41FitMpv","_previous":{"url":"https://system.test/book/arunas/center"},"_flash":{"old":[],"new":[]},"latest_permalink":"arunas"},"ip":"127.0.0.1","user_agent":"Mozilla/5.0 (iPhone; CPU iPhone OS 15_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/19G82 [FBAN/FBIOS;FBDV/iPhone9,3;FBMD/iPhone;FBSN/iOS;FBSV/15.6.1;FBSS/2;FBID/phone;FBLC/da_DK;FBOP/5]"}
EOF;

    $jsonString = '{"permalink":"arunas","session":{"_token":"BpqyiNyinnLamzer4jqzrh9NTyC6emFR41FitMpv","_previous":{"url":"https://system.test/book/arunas/center"},"_flash":{"old":[],"new":[]},"latest_permalink":"arunas"},"ip":"127.0.0.1","user_agent":"Mozilla/5.0 (iPhone; CPU iPhone OS 15_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/19G82 [FBAN/FBIOS;FBDV/iPhone9,3;FBMD/iPhone;FBSN/iOS;FBSV/15.6.1;FBSS/2;FBID/phone;FBLC/da_DK;FBOP/5]"}';
    $text = '[2022-08-25 11:16:17] local.DEBUG: '.$logText;

    $log = new LaravelLog($text, 'laravel.log');

    assertEquals('arunas', $log->context['permalink'] ?? null);
    assertEquals('Initiating facebook login.', $log->message);
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

it('strips extracted context when there\'s multiple contexts available', function () {
    config(['log-viewer.strip_extracted_context' => true]);
    $logText = <<<'EOF'
[2023-08-16 14:00:25] testing.INFO: Test message. ["one","two"] {"memory_usage":"78 MB","process_id":1234}
EOF;

    $log = new LaravelLog($logText);

    assertEquals('Test message.', $log->message);
    assertEquals(2, count($log->context));
    assertEquals(['one', 'two'], $log->context[0]);
    assertEquals(['memory_usage' => '78 MB', 'process_id' => 1234], $log->context[1]);
});

it('correctly handles objects with number keys', function () {
    $logText = <<<'EOF'
[2024-03-13 12:57:30] local.DEBUG: This is a log message {"array":{"10":"value1","20":"value2"}}
EOF;

    $log = new LaravelLog($logText);

    assertEquals('This is a log message', $log->message);
    assertEquals(['array' => ['10' => 'value1', '20' => 'value2']], $log->context);
});

it('can extract mail preview from a log', function () {
    $messageString = <<<'EOF'
[2023-08-24 15:51:14] local.DEBUG: From: sender@example.com
To: recipient@example.com
Cc: cc@example.com
Bcc: bcc@example.com
Subject: This is an email with common headers
Date: Thu, 24 Aug 2023 21:15:01 PST
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="----=_Part_1_1234567890"

------=_Part_1_1234567890
Content-Type: text/plain; charset="utf-8"

This is the text version of the email.

------=_Part_1_1234567890
Content-Type: text/html; charset="utf-8"

<html>
<head>
<title>This is an HTML email</title>
</head>
<body>
<h1>This is the HTML version of the email</h1>
</body>
</html>

------=_Part_1_1234567890
Content-Type: text/plain; charset="utf-8"
Content-Disposition: attachment; filename="example.txt"

Example attachment content

------=_Part_1_1234567890--
EOF;
    // The email string (as per RFC 5322) actually needs to use \r\n sequence instead of \n
    $messageString = str_replace("\n", "\r\n", $messageString);

    $log = new LaravelLog($messageString);

    expect($log->extra)->toHaveKey('mail_preview')
        ->and($log->extra['mail_preview'])->toBe([
            'id' => null,
            'subject' => 'This is an email with common headers',
            'from' => 'sender@example.com',
            'to' => 'recipient@example.com',
            'attachments' => [
                [
                    'content' => base64_encode('Example attachment content'),
                    'content_type' => 'text/plain; charset="utf-8"',
                    'filename' => 'example.txt',
                    'size_formatted' => Utils::bytesForHumans(strlen('Example attachment content')),
                ],
            ],
            'html' => <<<'EOF'
<html>
<head>
<title>This is an HTML email</title>
</head>
<body>
<h1>This is the HTML version of the email</h1>
</body>
</html>
EOF,
            'text' => 'This is the text version of the email.',
            'size_formatted' => Utils::bytesForHumans(strlen($messageString) - strlen('[2023-08-24 15:51:14] local.DEBUG: ')),
        ]);
});

it('filters stack traces in context when shorter stack traces is enabled', function () {
    session(['log-viewer:shorter-stack-traces' => true]);
    config([
        'log-viewer.shorter_stack_trace_excludes' => [
            '/vendor/symfony/',
            '/vendor/laravel/framework/',
        ],
    ]);

    $stackTrace = <<<'EOF'
#0 /app/Controllers/UserController.php(25): someFunction()
#1 /vendor/symfony/http-kernel/HttpKernel.php(158): handle()
#2 /vendor/laravel/framework/Illuminate/Pipeline/Pipeline.php(128): process()
#3 /app/Middleware/CustomMiddleware.php(42): handle()
#4 /vendor/symfony/routing/Router.php(89): route()
#5 /app/bootstrap/app.php(15): bootstrap()
EOF;

    $logText = <<<EOF
[2024-10-18 12:00:00] production.ERROR: Exception occurred {"exception":"$stackTrace"}
EOF;

    $log = new LaravelLog($logText);

    expect($log->context)->toHaveKey('exception')
        ->and($log->context['exception'])->toContain('#0 /app/Controllers/UserController.php(25): someFunction()')
        ->and($log->context['exception'])->toContain('#3 /app/Middleware/CustomMiddleware.php(42): handle()')
        ->and($log->context['exception'])->toContain('#5 /app/bootstrap/app.php(15): bootstrap()')
        ->and($log->context['exception'])->toContain('    ...')
        ->and($log->context['exception'])->not->toContain('/vendor/symfony/http-kernel/')
        ->and($log->context['exception'])->not->toContain('/vendor/laravel/framework/')
        ->and($log->context['exception'])->not->toContain('/vendor/symfony/routing/');
});

it('does not filter context when shorter stack traces is disabled', function () {
    session(['log-viewer:shorter-stack-traces' => false]);
    config([
        'log-viewer.shorter_stack_trace_excludes' => [
            '/vendor/symfony/',
            '/vendor/laravel/framework/',
        ],
    ]);

    $stackTrace = <<<'EOF'
#0 /app/Controllers/UserController.php(25): someFunction()
#1 /vendor/symfony/http-kernel/HttpKernel.php(158): handle()
#2 /vendor/laravel/framework/Illuminate/Pipeline/Pipeline.php(128): process()
EOF;

    $logText = <<<EOF
[2024-10-18 12:00:00] production.ERROR: Exception occurred {"exception":"$stackTrace"}
EOF;

    $log = new LaravelLog($logText);

    expect($log->context)->toHaveKey('exception')
        ->and($log->context['exception'])->toBe($stackTrace)
        ->and($log->context['exception'])->toContain('/vendor/symfony/http-kernel/')
        ->and($log->context['exception'])->toContain('/vendor/laravel/framework/');
});
