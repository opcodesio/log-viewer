<?php

use Illuminate\Support\Facades\Http;
use Opcodes\LogViewer\Facades\LogViewer;

beforeEach(function () {
    config(['log-viewer.hosts' => $hostConfigs = [
        'test-host' => [
            'host' => 'https://example.com/log-viewer',
            'headers' => [
                'Authorization' => 'Bearer 1234',
            ],
        ],
    ]]);
});

it('can fire and handle a successful health check to a host', function () {
    $host = LogViewer::getHost('test-host');

    Http::fake(['*' => Http::response('OK', 200)]);

    expect($host->healthCheck())->toBeTrue();

    Http::assertSent(function (Illuminate\Http\Client\Request $request) use ($host) {
        return $request->url() === $host->host.'/health-check'
            && $request->method() === 'GET'
            && collect($host->headers)->every(fn ($value, $key) => $request->hasHeader($key, $value));
    });
});
