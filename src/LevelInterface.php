<?php

namespace Opcodes\LogViewer;

interface LevelInterface
{
    public function __construct(string $value);

    public static function from(string $value = null): self;
    public static function caseValues(): array;
    public function getName(): string;
    public function getClass(): string;
}
