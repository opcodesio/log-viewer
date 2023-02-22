<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Http;
use Opcodes\LogViewer\Utils\Utils;

class Host
{
    public string $identifier;

    public function __construct(
        public string $host,
        public array $headers = [],
    ) {
        $this->identifier = Utils::shortMd5($host);
    }

    public static function fromConfig(array $config = []): self
    {
        return new static($config['host'], $config['headers'] ?? []);
    }

    public function healthCheck(): bool
    {
        return Http::withHeaders($this->headers)
            ->get($this->host . '/health-check')
            ->throw()
            ->ok();
    }
}
