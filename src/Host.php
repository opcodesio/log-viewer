<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Facades\Http;
use Opcodes\LogViewer\Utils\Utils;

class Host
{
    public bool $is_remote;

    public function __construct(
        public string|null $identifier,
        public string $name,
        public string|null $host = null,
        public array|null $headers = null,
    ) {
        $this->is_remote = $this->isRemote();
    }

    public static function fromConfig(string|int $identifier, array $config = []): self
    {
        if (!is_string($config['host']) || empty($config['host'])) {
            throw new \InvalidArgumentException('Host configuration must contain a valid host URL in the "host" key.');
        }

        return new static(
            is_string($identifier) ? $identifier : Utils::shortMd5($config['host']),
            $config['name'] ?? (is_string($identifier) ? $identifier : $config['host']),
            $config['host'],
            $config['headers'] ?? [],
        );
    }

    public function isRemote(): bool
    {
        return !is_null($this->host);
    }

    public function healthCheck(): bool
    {
        return Http::withHeaders($this->headers)
            ->get($this->host.'/health-check')
            ->throw()
            ->ok();
    }
}
