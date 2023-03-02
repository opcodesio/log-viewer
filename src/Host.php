<?php

namespace Opcodes\LogViewer;

use Opcodes\LogViewer\Utils\Utils;

class Host
{
    public bool $is_remote;

    public function __construct(
        public string|null $identifier,
        public string $name,
        public string|null $host = null,
        public array|null $headers = null,
        public array|null $auth = null,
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
        );
    }

    public function isRemote(): bool
    {
        return ! is_null($this->host);
    }
}
