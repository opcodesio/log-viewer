<?php

namespace Opcodes\LogViewer;

use Illuminate\Support\Collection;

class HostCollection extends Collection
{
    public static function fromConfig(array $config = []): self
    {
        return new static(array_map(
            fn (array $hostConfig, $identifier) => Host::fromConfig($identifier, $hostConfig),
            $config,
            array_keys($config)
        ));
    }
}
