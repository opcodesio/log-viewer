<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class AccessLog
{
    public string $ip;

    public string $identity;

    public string $remoteUser;

    public CarbonInterface $datetime;

    public string $method;

    public string $path;

    public string $httpVersion;

    public int $statusCode;

    public int $contentLength;

    public string $referrer;

    public string $userAgent;

    public ?string $fileIdentifier = null;

    public ?int $filePosition = null;

    public function __construct(
        $ip, $identity, $remoteUser, $datetime, $method, $path, $httpVersion,
        $statusCode, $contentLength, $referrer, $userAgent,
        $fileIdentifier = null, $filePosition = null,
    ) {
        $this->ip = $ip;
        $this->identity = $identity;
        $this->remoteUser = $remoteUser;
        $this->datetime = Carbon::parse($datetime)->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );
        $this->method = $method;
        $this->path = $path;
        $this->httpVersion = $httpVersion;
        $this->statusCode = intval($statusCode);
        $this->contentLength = intval($contentLength);
        $this->referrer = $referrer;
        $this->userAgent = $userAgent;
        $this->fileIdentifier = $fileIdentifier;
        $this->filePosition = $filePosition;
    }

    public static function fromString(string $line, string $fileIdentifier = null, int $filePosition = null): self
    {
        $regex = '/(\S+) (\S+) (\S+) \[(.+)\] "(\S+) (\S+) (\S+)" (\S+) (\S+) "([^"]*)" "([^"]*)"/';
        preg_match($regex, $line, $matches);

        return new self(
            $matches[1],
            $matches[2],
            $matches[3],
            $matches[4],
            $matches[5],
            $matches[6],
            $matches[7],
            $matches[8],
            $matches[9],
            $matches[10],
            $matches[11],
            $fileIdentifier,
            $filePosition,
        );
    }
}
