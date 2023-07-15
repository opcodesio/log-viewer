<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HttpAccessLog extends HttpLog
{
    public static string $regex = '/(\S+) (\S+) (\S+) \[(.+)\] "(\S+) (\S+) (\S+)" (\S+) (\S+) "([^"]*)" "([^"]*)"/';

    public ?CarbonInterface $datetime;

    public ?string $level;

    public ?string $ip;

    public ?string $identity;

    public ?string $remoteUser;

    public ?string $method;

    public ?string $path;

    public ?string $httpVersion;

    public ?int $statusCode;

    public ?int $contentLength;

    public ?string $referrer;

    public ?string $userAgent;

    public function __construct(
        public string $text,
        public ?string $fileIdentifier = null,
        public ?int $filePosition = null,
        public ?int $index = null,
    ) {
        parent::__construct($text, $fileIdentifier, $filePosition, $index);

        $matches = $this->parseText($this->text);

        $this->ip = $matches['ip'];
        $this->identity = $matches['identity'];
        $this->remoteUser = $matches['remoteUser'];
        $this->datetime = $this->parseDateTime($matches['datetime'])?->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );
        $this->method = $matches['method'];
        $this->path = $matches['path'];
        $this->httpVersion = $matches['httpVersion'];
        $this->statusCode = isset($matches['statusCode']) ? intval($matches['statusCode']) : null;
        $this->level = $this->statusCode;
        $this->contentLength = isset($matches['contentLength']) ? intval($matches['contentLength']) : null;
        $this->referrer = $matches['referrer'];
        $this->userAgent = $matches['userAgent'];
    }

    public function parseText(string $text = ''): array
    {
        preg_match(self::$regex, $text, $matches);

        return [
            'ip' => $matches[1] ?? null,
            'identity' => $matches[2] ?? null,
            'remoteUser' => $matches[3] ?? null,
            'datetime' => $matches[4] ?? null,
            'method' => $matches[5] ?? null,
            'path' => $matches[6] ?? null,
            'httpVersion' => $matches[7] ?? null,
            'statusCode' => $matches[8] ?? null,
            'level' => $matches[8] ?? null,
            'contentLength' => $matches[9] ?? null,
            'referrer' => $matches[10] ?? null,
            'userAgent' => $matches[11] ?? null,
        ];
    }

    public function parseDateTime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::parse($datetime) : null;
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        $matches = [];
        $result = preg_match(static::$regex, $text, $matches) === 1;

        if ($result) {
            $timestamp = strtotime($matches[4]);
            $level = $matches[8];
        }

        return $result;
    }

    public static function levelClass(): string
    {
        return StatusCodeLevel::class;
    }
}
