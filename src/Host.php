<?php

namespace Opcodes\LogViewer;

use Opcodes\LogViewer\Utils\Utils;

class Host
{
    public bool $is_remote;

    public function __construct(
        public ?string $identifier,
        public string $name,
        public ?string $host = null,
        public ?array $headers = null,
        public ?array $auth = null,
        public ?bool $verifyServerCertificate = true,
    ) {
        $this->is_remote = $this->isRemote();
    }

    public static function fromConfig(string|int $identifier, array $config = []): self
    {
        return new static(
            is_string($identifier) ? $identifier : Utils::shortMd5($config['host']),
            $config['name'] ?? (is_string($identifier) ? $identifier : $config['host']),
            $config['host'] ?? null,
            $config['headers'] ?? [],
            $config['auth'] ?? [],
            $config['verify_server_certificate'] ?? true,
        );
    }

    public function isRemote(): bool
    {
        return ! is_null($this->host);
    }
}
