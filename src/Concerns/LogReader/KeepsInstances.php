<?php

namespace Opcodes\LogViewer\Concerns\LogReader;

use Opcodes\LogViewer\LogFile;

trait KeepsInstances
{
    /**
     * Cached LogReader instances.
     */
    public static array $_instances = [];

    public static function instance(LogFile $file): static
    {
        if (! isset(self::$_instances[$file->path])) {
            self::$_instances[$file->path] = new self($file);
        }

        return self::$_instances[$file->path];
    }

    public static function clearInstance(LogFile $file): void
    {
        if (isset(self::$_instances[$file->path])) {
            unset(self::$_instances[$file->path]);
        }
    }

    public static function clearInstances(): void
    {
        self::$_instances = [];
    }
}
