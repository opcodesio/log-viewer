<?php

use Illuminate\Support\Facades\Http;
use Opcodes\LogViewer\Facades\LogViewer;

beforeEach(function () {
    config(['log-viewer.hosts' => [
        'remote' => [
            'host' => 'https://example.com/log-viewer-remote',
            'headers' => ['Authorization' => 'Bearer 1234567890'],
        ],
    ]]);
    $this->remoteHost = LogViewer::getHosts()->first(fn ($host) => $host->isRemote());
});

it('can forward request to a different host', function ($routeName) {
    Http::fake(['*' => Http::response($proxiedResponseBody = ['files' => ['one', 'two']])]);

    $response = $this->getJson(route($routeName, ['host' => 'remote', 'foo' => 'bar']));
    $response->assertOk()->assertJson($proxiedResponseBody);

    Http::assertSent(function (Illuminate\Http\Client\Request $request) use ($routeName) {
        $expectedNewPath = Str::replaceFirst(
            route('log-viewer.index'),  // http://localhost/log-viewer
            $this->remoteHost->host,                      // https://example.com/log-viewer-remote
            route($routeName, ['foo' => 'bar'])   // no longer includes 'host' query param
        );

        return $request->url() === $expectedNewPath
            && $request->method() === 'GET'
            && collect($this->remoteHost->headers)->every(fn ($value, $key) => $request->hasHeader($key, $value));
    });
})->with([
    'log-viewer.folders',
    'log-viewer.files',
    'log-viewer.logs',
]);
