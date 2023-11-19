<?php

use Opcodes\LogViewer\Logs\LaravelLog;
use Opcodes\LogViewer\Utils\Utils;

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
