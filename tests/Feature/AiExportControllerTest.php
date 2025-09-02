<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Opcodes\LogViewer\Services\AiExport\AiExportService;

beforeEach(function () {
    Config::set('cache.default', 'array');

    Config::set('log-viewer.ai_export.enabled', true);
    Config::set('log-viewer.ai_export.providers.chatgpt', [
        'enabled' => true,
        'url' => 'https://chat.openai.com/',
        'max_characters' => 8000,
    ]);
    Config::set('log-viewer.ai_export.providers.claude', [
        'enabled' => true,
        'url' => 'https://claude.ai/new',
        'max_characters' => 10000,
    ]);

    RateLimiter::clear('ai-export:127.0.0.1');
});

describe('AI Export Providers Endpoint', function () {
    it('returns list of available providers', function () {
        $response = $this->getJson(route('log-viewer.ai.providers'));

        $response->assertOk()
            ->assertJsonStructure([
                'providers' => [
                    '*' => ['key', 'name', 'icon', 'enabled'],
                ],
            ])
            ->assertJsonFragment(['key' => 'chatgpt', 'name' => 'ChatGPT'])
            ->assertJsonFragment(['key' => 'claude', 'name' => 'Claude']);
    });

    it('returns 403 when feature is disabled', function () {
        Config::set('log-viewer.ai_export.enabled', false);

        $response = $this->getJson(route('log-viewer.ai.providers'));

        $response->assertForbidden()
            ->assertJson(['error' => 'AI Export feature is disabled']);
    });
});

describe('AI Export Endpoint', function () {
    it('validates required fields', function () {
        $response = $this->postJson(route('log-viewer.ai.export'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider', 'log_index']);
    });

    it('rejects invalid provider', function () {
        $response = $this->postJson(route('log-viewer.ai.export'), [
            'provider' => 'invalid-provider',
            'log_index' => 0,
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid provider']);
    });

    it('enforces rate limiting', function () {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('log-viewer.ai.export'), [
                'provider' => 'chatgpt',
                'log_index' => 0,
            ]);
        }

        $response = $this->postJson(route('log-viewer.ai.export'), [
            'provider' => 'chatgpt',
            'log_index' => 0,
        ]);

        $response->assertStatus(429)
            ->assertJson(['error' => 'Too many requests. Please try again later.']);
    });

    it('returns 404 when log not found', function () {
        $response = $this->postJson(route('log-viewer.ai.export'), [
            'provider' => 'chatgpt',
            'log_index' => 99999,
        ]);

        $response->assertNotFound()
            ->assertJson(['error' => 'Log not found']);
    });

    it('returns 403 when feature is disabled', function () {
        Config::set('log-viewer.ai_export.enabled', false);

        $response = $this->postJson(route('log-viewer.ai.export'), [
            'provider' => 'chatgpt',
            'log_index' => 0,
        ]);

        $response->assertForbidden()
            ->assertJson(['error' => 'AI Export feature is disabled']);
    });

    it('returns correct response structure when successful', function () {

        $controller = Mockery::mock(\Opcodes\LogViewer\Http\Controllers\AiExportController::class.'[getLogByIndex]', [
            app(AiExportService::class),
        ])->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('getLogByIndex')
            ->with(0, null)
            ->andReturn([
                'level_name' => 'ERROR',
                'level_class' => 'danger',
                'message' => 'Test error',
                'datetime' => '2024-01-01 00:00:00',
                'full_text' => 'Test error details',
                'context' => [],
                'extra' => [],
            ]);

        $this->app->instance(\Opcodes\LogViewer\Http\Controllers\AiExportController::class, $controller);

        $mockService = Mockery::mock(AiExportService::class);
        $mockService->shouldReceive('getProvider')
            ->with('chatgpt')
            ->andReturn(new \Opcodes\LogViewer\Services\AiExport\Providers\ChatGptProvider);
        $mockService->shouldReceive('generateProviderUrl')
            ->andReturn('https://chat.openai.com/?q=test');
        $mockService->shouldReceive('formatLogForAi')
            ->andReturn('## Test Markdown');

        $this->app->instance(AiExportService::class, $mockService);

        $response = $this->postJson(route('log-viewer.ai.export'), [
            'provider' => 'chatgpt',
            'log_index' => 0,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'markdown',
                'provider' => ['key', 'name'],
            ]);
    });
});

describe('Copy as Markdown Endpoint', function () {
    it('validates required fields', function () {
        $response = $this->postJson(route('log-viewer.ai.copy-markdown'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['log_index']);
    });

    it('returns 404 when log not found', function () {
        $response = $this->postJson(route('log-viewer.ai.copy-markdown'), [
            'log_index' => 99999,
        ]);

        $response->assertNotFound()
            ->assertJson(['error' => 'Log not found']);
    });

    it('returns markdown when successful', function () {

        $mockService = Mockery::mock(AiExportService::class);
        $mockService->shouldReceive('setCopyAsMarkdown')
            ->with(true)
            ->andReturnSelf();
        $mockService->shouldReceive('formatLogForAi')
            ->andReturn('## Test Markdown Content');

        $this->app->instance(AiExportService::class, $mockService);

        $controller = Mockery::mock(\Opcodes\LogViewer\Http\Controllers\AiExportController::class.'[getLogByIndex]', [
            $mockService,  // Passar o serviço mockado diretamente
        ])->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('getLogByIndex')
            ->with(0, null)
            ->andReturn([
                'level_name' => 'ERROR',
                'level_class' => 'danger',
                'message' => 'Test error',
                'datetime' => '2024-01-01 00:00:00',
                'full_text' => 'Test error details',
                'context' => [],
                'extra' => [],
            ]);

        $this->app->instance(\Opcodes\LogViewer\Http\Controllers\AiExportController::class, $controller);

        $response = $this->postJson(route('log-viewer.ai.copy-markdown'), [
            'log_index' => 0,
        ]);

        $response->assertOk()
            ->assertJson(['markdown' => '## Test Markdown Content']);
    });

    it('returns 403 when feature is disabled', function () {
        Config::set('log-viewer.ai_export.enabled', false);

        $response = $this->postJson(route('log-viewer.ai.copy-markdown'), [
            'log_index' => 0,
        ]);

        $response->assertForbidden()
            ->assertJson(['error' => 'AI Export feature is disabled']);
    });
});
