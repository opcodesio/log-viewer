<?php

namespace Opcodes\LogViewer\Concerns\LogFile;

trait HasMetadata
{
    protected array $metadata;

    public function setMetadata(string $attribute, $value): void
    {
        $this->metadata[$attribute] = $value;
    }

    public function getMetadata(?string $attribute = null, $default = null): mixed
    {
        if (! isset($this->metadata)) {
            $this->loadMetadata();
        }

        if (isset($attribute)) {
            return $this->metadata[$attribute] ?? $default;
        }

        return $this->metadata;
    }

    public function saveMetadata(): void
    {
        $this->saveMetadataToCache($this->metadata);
    }

    protected function loadMetadata(): void
    {
        $this->metadata = $this->loadMetadataFromCache();
    }
}
