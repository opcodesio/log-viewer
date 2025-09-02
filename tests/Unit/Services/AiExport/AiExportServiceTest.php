<?php

use Opcodes\LogViewer\Services\AiExport\AiExportService;
use Opcodes\LogViewer\Services\AiExport\Providers\ChatGptProvider;
use Opcodes\LogViewer\Services\AiExport\Providers\ClaudeProvider;

beforeEach(function () {
    $this->service = new AiExportService;
});

it('registers default providers', function () {
    $providers = $this->service->getProviders();

    expect($providers)
        ->toHaveCount(2)
        ->and($providers->keys())
        ->toContain('chatgpt', 'claude');
});

it('can retrieve specific provider', function () {
    $chatgpt = $this->service->getProvider('chatgpt');
    $claude = $this->service->getProvider('claude');

    expect($chatgpt)->toBeInstanceOf(ChatGptProvider::class)
        ->and($claude)->toBeInstanceOf(ClaudeProvider::class);
});

it('returns null for non-existent provider', function () {
    expect($this->service->getProvider('non-existent'))->toBeNull();
});

it('formats log correctly for AI', function () {
    $log = [
        'level_name' => 'ERROR',
        'message' => 'Test error message',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => "ErrorException: Test error in /app/test.php:10\nStack trace here...",
        'context' => ['user_id' => 123, 'action' => 'test'],
    ];

    $markdown = $this->service->formatLogForAi($log);

    expect($markdown)
        ->toContain('## Log Error Analysis Request')
        ->toContain('**Level**: ERROR')
        ->toContain('**Message**: Test error message')
        ->toContain('Stack trace here...')
        ->toContain('"user_id": 123')
        ->toContain('Please analyze this Laravel error');
});

it('sanitizes sensitive data from content', function () {
    $log = [
        'level_name' => 'ERROR',
        'message' => 'Database connection failed',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => 'Connection failed with password=secret123 and api_key=sk-1234567890',
        'context' => [
            'password' => 'mypassword',
            'api_key' => 'secret_key',
            'credit_card' => '4111111111111111',
            'cpf' => '123.456.789-00',
        ],
    ];

    $markdown = $this->service->formatLogForAi($log);

    expect($markdown)
        ->not->toContain('secret123')
        ->not->toContain('sk-1234567890')
        ->not->toContain('mypassword')
        ->not->toContain('secret_key')
        ->not->toContain('4111111111111111')
        ->not->toContain('123.456.789-00')
        ->toContain('[REDACTED]')
        ->toContain('password=[REDACTED]')
        ->toContain('api_key=[REDACTED]');
});

it('extracts exception class from stack trace', function () {
    $log = [
        'level_name' => 'ERROR',
        'message' => 'Test error',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => 'Illuminate\\Database\\QueryException: SQLSTATE[42S02]: Base table not found',
        'context' => [],
    ];

    $markdown = $this->service->formatLogForAi($log);

    expect($markdown)->toContain('**Exception Class**: Illuminate\\Database\\QueryException');
});

it('handles empty context gracefully', function () {
    $log = [
        'level_name' => 'ERROR',
        'message' => 'Test error',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => 'Error occurred',
        'context' => [],
    ];

    $markdown = $this->service->formatLogForAi($log);

    expect($markdown)
        ->not->toContain('### Context')
        ->not->toContain('```json');
});

it('limits stack trace lines', function () {

    $longStackTrace = implode("\n", array_fill(0, 100, 'Line of stack trace'));

    $log = [
        'level_name' => 'ERROR',
        'message' => 'Test error',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => $longStackTrace,
        'context' => [],
    ];

    $markdown = $this->service->formatLogForAi($log);
    $lines = explode("\n", $markdown);

    expect($markdown)->toContain('[Stack trace truncated]');
});

it('includes Laravel and PHP version info', function () {
    $log = [
        'level_name' => 'ERROR',
        'message' => 'Test error',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => 'Error',
        'context' => [],
    ];

    $markdown = $this->service->formatLogForAi($log);

    expect($markdown)
        ->toContain('**PHP Version**:')
        ->toContain('**Laravel Version**:')
        ->toContain('**Log Viewer Version**:');
});

it('generates provider URL correctly', function () {
    $log = [
        'level_name' => 'ERROR',
        'message' => 'Test error',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => 'Error',
        'context' => [],
    ];

    $url = $this->service->generateProviderUrl('chatgpt', $log);

    expect($url)
        ->toBeString()
        ->toStartWith('https://chat.openai.com/?q=');
});

it('returns null for invalid provider in generateProviderUrl', function () {
    $log = [
        'level_name' => 'ERROR',
        'message' => 'Test error',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => 'Error',
        'context' => [],
    ];

    $url = $this->service->generateProviderUrl('invalid-provider', $log);

    expect($url)->toBeNull();
});

it('sanitizes JWT tokens', function () {
    $log = [
        'level_name' => 'ERROR',
        'message' => 'Auth failed',
        'datetime' => '2024-01-01 12:00:00',
        'full_text' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
        'context' => [],
    ];

    $markdown = $this->service->formatLogForAi($log);

    expect($markdown)
        ->not->toContain('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9')
        ->toContain('Bearer [REDACTED_JWT]');
});
