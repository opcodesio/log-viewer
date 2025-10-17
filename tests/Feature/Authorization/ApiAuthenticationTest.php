<?php

use Opcodes\LogViewer\Facades\LogViewer;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

test('auth callback is called for API routes', function () {
    $callbackInvoked = false;

    LogViewer::auth(function ($request) use (&$callbackInvoked) {
        $callbackInvoked = true;

        return true;
    });

    getJson(route('log-viewer.folders'))->assertOk();

    expect($callbackInvoked)->toBeTrue();
});

test('auth callback denies access to API routes when it returns false', function () {
    LogViewer::auth(fn ($request) => false);

    getJson(route('log-viewer.folders'))->assertForbidden();
});

test('authentication works when APP_URL is empty using same-domain fallback', function () {
    config(['app.url' => '']);

    LogViewer::auth(fn ($request) => true);

    $response = getJson(route('log-viewer.folders'), [
        'referer' => 'http://localhost/',
    ]);

    $response->assertOk();
});

test('authentication works when APP_URL matches request domain', function () {
    config(['app.url' => 'http://example.com']);

    LogViewer::auth(fn ($request) => true);

    $response = getJson('http://example.com/log-viewer/api/folders', [
        'referer' => 'http://example.com/',
    ]);

    $response->assertOk();
});

test('authentication fails when APP_URL is set but referer does not match', function () {
    config(['app.url' => 'http://configured-domain.com']);

    // Auth callback that checks for authenticated user (simulating real-world usage)
    LogViewer::auth(function ($request) {
        // In real usage, Auth::user() would be null because session middleware isn't applied
        // For this test, we'll simulate that by checking if session is available
        if (! $request->hasSession() || ! $request->session()->isStarted()) {
            return false;
        }

        return true;
    });

    // Request from different domain with referer that doesn't match stateful domains
    $response = getJson('http://different-domain.com/log-viewer/api/folders', [
        'referer' => 'http://different-domain.com/',
    ]);

    // Should fail because session middleware is not applied, causing auth callback to return false
    $response->assertForbidden();
});

test('same-domain requests work without APP_URL configured', function () {
    config(['app.url' => null]);

    LogViewer::auth(fn ($request) => true);

    // Simulate request from same domain
    $response = getJson('http://production.example.com/log-viewer/api/folders', [
        'referer' => 'http://production.example.com/log-viewer',
    ]);

    $response->assertOk();
});

test('same-domain requests with custom port work without APP_URL', function () {
    config(['app.url' => null]);

    LogViewer::auth(fn ($request) => true);

    // Simulate request from same domain with custom port
    $response = getJson('http://localhost:8080/log-viewer/api/folders', [
        'referer' => 'http://localhost:8080/log-viewer',
    ]);

    $response->assertOk();
});

test('cross-domain requests are rejected when APP_URL is empty', function () {
    config(['app.url' => null]);

    // Auth callback that checks for session (simulating Auth::check() behavior)
    LogViewer::auth(function ($request) {
        if (! $request->hasSession() || ! $request->session()->isStarted()) {
            return false;
        }

        return true;
    });

    // Request to one domain with referer from different domain
    $response = getJson('http://domain-a.com/log-viewer/api/folders', [
        'referer' => 'http://domain-b.com/',
    ]);

    // Should fail because domains don't match, session middleware not applied
    $response->assertForbidden();
});

test('requests without referer or origin are rejected', function () {
    config(['app.url' => null]);

    // Auth callback that checks for session
    LogViewer::auth(function ($request) {
        if (! $request->hasSession() || ! $request->session()->isStarted()) {
            return false;
        }

        return true;
    });

    // Request without referer header
    $response = getJson(route('log-viewer.folders'));

    // Should fail because we can't determine if it's from frontend, so session middleware not applied
    $response->assertForbidden();
});

test('localhost requests work by default regardless of APP_URL', function () {
    config(['app.url' => 'http://production.com']);

    LogViewer::auth(fn ($request) => true);

    // Localhost is in the default stateful domains
    $response = getJson('http://localhost/log-viewer/api/folders', [
        'referer' => 'http://localhost/',
    ]);

    $response->assertOk();
});

test('127.0.0.1 requests work by default regardless of APP_URL', function () {
    config(['app.url' => 'http://production.com']);

    LogViewer::auth(fn ($request) => true);

    // 127.0.0.1 is in the default stateful domains
    $response = getJson('http://127.0.0.1/log-viewer/api/folders', [
        'referer' => 'http://127.0.0.1/',
    ]);

    $response->assertOk();
});

test('custom stateful domains override APP_URL behavior', function () {
    config([
        'app.url' => null,
        'log-viewer.api_stateful_domains' => ['custom-domain.com'],
    ]);

    LogViewer::auth(fn ($request) => true);

    // Custom domain should work
    $response = getJson('http://custom-domain.com/log-viewer/api/folders', [
        'referer' => 'http://custom-domain.com/',
    ]);

    $response->assertOk();
});
