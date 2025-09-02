<?php

namespace Opcodes\LogViewer\Services\AiExport\Contracts;

interface AiProviderInterface
{
    public function getName(): string;

    public function getIcon(): string;

    public function generateUrl(string $markdownContent): string;

    public function isEnabled(): bool;

    public function getMaxCharacterLimit(): int;

    public function getConfig(): array;
}
