<?php

namespace Opcodes\LogViewer\Logs;

use Opcodes\LogViewer\LogTypeRegistrar;

class LogType
{
    const DEFAULT = 'log';
    const LARAVEL = 'laravel';
    const HTTP_ACCESS = 'http_access';
    const HTTP_ERROR_APACHE = 'http_error_apache';
    const HTTP_ERROR_NGINX = 'http_error_nginx';
    const HORIZON_OLD = 'horizon_old';
    const HORIZON = 'horizon';
    const PHP_FPM = 'php_fpm';
    const POSTGRES = 'postgres';
    const REDIS = 'redis';
    const SUPERVISOR = 'supervisor';

    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        $class = $this->logClass();

        return match ($this->value) {
            self::LARAVEL => 'Laravel',
            self::HTTP_ACCESS => 'HTTP Access',
            self::HTTP_ERROR_APACHE => 'HTTP Error (Apache)',
            self::HTTP_ERROR_NGINX => 'HTTP Error (Nginx)',
            self::HORIZON_OLD => 'Horizon (Old)',
            self::HORIZON => 'Horizon',
            self::PHP_FPM => 'PHP-FPM',
            self::POSTGRES => 'Postgres',
            self::REDIS => 'Redis',
            self::SUPERVISOR => 'Supervisor',
            default => isset($class) ? ($class::$name ?? 'Unknown') : 'Unknown',
        };
    }

    /**
     * @return string|Log|null
     */
    public function logClass(): ?string
    {
        return app(LogTypeRegistrar::class)->getClass($this->value);
    }

    public function isUnknown(): bool
    {
        return $this->value === static::DEFAULT;
    }
}
