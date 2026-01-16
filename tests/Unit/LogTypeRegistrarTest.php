<?php

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\Logs\LogType;
use Opcodes\LogViewer\LogTypeRegistrar;

beforeEach(function () {
    $this->registrar = new LogTypeRegistrar;
});

test('laravel.log is detected as Laravel log', function () {
    $path = storage_path('logs/laravel.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('laravel-YYYY-MM-DD.log is detected as Laravel log', function () {
    $path = storage_path('logs/laravel-2024-01-16.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('laravel-mychannel.log is detected as Laravel log', function () {
    $path = storage_path('logs/laravel-mychannel.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('laravel-anything.log is detected as Laravel log', function () {
    $path = storage_path('logs/laravel-anything.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('laravel-errors.log is detected as Laravel log', function () {
    $path = storage_path('logs/laravel-errors.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('php-fpm.log is detected as PHP-FPM log', function () {
    $path = storage_path('logs/php-fpm.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::PHP_FPM);
});

test('access.log is detected as HTTP access log', function () {
    $path = storage_path('logs/access.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::HTTP_ACCESS);
});

test('postgres.log is detected as Postgres log', function () {
    $path = storage_path('logs/postgres.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::POSTGRES);
});

test('redis.log is detected as Redis log', function () {
    $path = storage_path('logs/redis.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::REDIS);
});

test('supervisor.log is detected as Supervisor log', function () {
    $path = storage_path('logs/supervisor.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::SUPERVISOR);
});

test('unknown.log returns null', function () {
    $path = storage_path('logs/unknown.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBeNull();
});

test('custom.log returns null', function () {
    $path = storage_path('logs/custom.log');
    file_put_contents($path, '');

    $logFile = new LogFile($path);
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBeNull();
});
