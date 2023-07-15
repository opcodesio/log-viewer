<?php

namespace Opcodes\LogViewer;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HttpNginxErrorLog extends HttpLog
{
    public static string $regex = '/^(?P<datetime>[\d+\/ :]+) \[(?P<errortype>.+)\] .*?: (?P<errormessage>.+?)(?:, client: (?P<client>.+?))?(?:, server: (?P<server>.+?))?(?:, request: "?(?P<request>.+?)"?)?(?:, host: "?(?P<host>.+?)"?)?$/';

    public ?CarbonInterface $datetime;

    public ?string $level;

    public ?string $message;

    public ?string $client;

    public ?string $server;

    public ?string $request;

    public ?string $host;

    public function __construct(
        public string $text,
        public ?string $fileIdentifier = null,
        public ?int $filePosition = null,
        public ?int $index = null,
    ) {
        parent::__construct($text, $fileIdentifier, $filePosition, $index);

        $matches = $this->parseText($text);

        $this->datetime = $this->parseDateTime($matches['datetime'])?->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );
        $this->level = $matches['level'];
        $this->message = $matches['message'];
        $this->client = $matches['client'];
        $this->server = $matches['server'];
        $this->request = $matches['request'];
        $this->host = $matches['host'];
    }

    public function parseText(string $text): array
    {
        $result = preg_match(self::$regex, $this->text, $matches);

        return [
            'datetime' => $matches['datetime'] ?? null,
            'level' => $matches['errortype'] ?? null,
            'message' => $matches['errormessage'] ?? null,
            'client' => isset($matches['client']) ? ($matches['client'] ?: null) : null,
            'server' => $matches['server'] ?? null,
            'request' => $matches['request'] ?? null,
            'host' => $matches['host'] ?? null,
        ];
    }

    public function parseDateTime(?string $datetime): ?CarbonInterface
    {
        return $datetime ? Carbon::createFromFormat('Y/m/d H:i:s', $datetime) : null;
    }

    public static function matches(string $text, int &$timestamp = null, string &$level = null): bool
    {
        $matches = [];
        $result = preg_match(static::$regex, $text, $matches) === 1;

        if ($result) {
            $timestamp = Carbon::createFromFormat('Y/m/d H:i:s', $matches['datetime'])->timestamp;
            $level = $matches['errortype'];
        }

        return $result;
    }

    public static function levelClass(): string
    {
        return Level::class;
    }
}
