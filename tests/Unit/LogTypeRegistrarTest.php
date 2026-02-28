<?php

use Opcodes\LogViewer\Logs\LogType;
use Opcodes\LogViewer\LogTypeRegistrar;

beforeEach(function () {
    $this->registrar = new LogTypeRegistrar;
});

test('laravel.log is detected as Laravel log', function () {
    $logFile = generateLogFile('laravel.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('laravel-YYYY-MM-DD.log is detected as Laravel log', function () {
    $logFile = generateLogFile('laravel-2024-01-16.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('laravel-mychannel.log is detected as Laravel log', function () {
    $logFile = generateLogFile('laravel-mychannel.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('laravel-anything.log is detected as Laravel log', function () {
    $logFile = generateLogFile('laravel-anything.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('laravel-errors.log is detected as Laravel log', function () {
    $logFile = generateLogFile('laravel-errors.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::LARAVEL);
});

test('php-fpm.log is detected as PHP-FPM log', function () {
    $logFile = generateLogFile('php-fpm.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::PHP_FPM);
});

test('access.log is detected as HTTP access log', function () {
    $logFile = generateLogFile('access.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::HTTP_ACCESS);
});

test('postgres.log is detected as Postgres log', function () {
    $logFile = generateLogFile('postgres.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::POSTGRES);
});

test('redis.log is detected as Redis log', function () {
    $logFile = generateLogFile('redis.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::REDIS);
});

test('supervisor.log is detected as Supervisor log', function () {
    $logFile = generateLogFile('supervisor.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBe(LogType::SUPERVISOR);
});

test('unknown.log returns null', function () {
    $logFile = generateLogFile('unknown.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBeNull();
});

test('custom.log returns null', function () {
    $logFile = generateLogFile('custom.log');
    $type = $this->registrar->guessTypeFromFileName($logFile);

    expect($type)->toBeNull();
});
