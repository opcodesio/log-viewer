<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;

beforeEach(function () {
    config(['log-viewer.hosts' => [
        'remote' => [
            'host' => 'https://example.com/log-viewer-remote',
            'headers' => ['Authorization' => 'Bearer 1234567890'],
        ],
    ]]);
    $this->remoteHost = LogViewer::getHosts()->remote()->first();
});

function expectedNewUrl($originalUrl, Opcodes\LogViewer\Host $host): string
{
    $newUrl = Str::replaceFirst(
        route('log-viewer.index'), // http://localhost/log-viewer
        $host->host,                     // https://example.com/log-viewer-remote
        $originalUrl
    );

    $queryString = parse_url($newUrl, PHP_URL_QUERY);
    parse_str($queryString, $queryParams);
    unset($queryParams['host']);

    // rebuild the newUrl with the new query params
    $newUrl = Str::replaceFirst($queryString, http_build_query($queryParams), $newUrl);

    return rtrim($newUrl, '?');
}

it('can forward request to a different host', function ($method, $routeName, $routeParams = []) {
    Http::fake(['*' => Http::response($proxiedResponseBody = ['files' => ['one', 'two']])]);

    // First, the original URL, not being proxied
    $url = route($routeName, $routeParams);
    $this->json($method, $url)->assertJsonMissing(['message' => 'Host configuration not found.']);

    Http::assertNothingSent();

    $newUrl = route($routeName, $routeParams + ['host' => $this->remoteHost->identifier]);

    $this->json($method, $newUrl)
        ->assertOk()
        ->assertJson($proxiedResponseBody);

    Http::assertSent(function (Illuminate\Http\Client\Request $request) use ($newUrl, $method) {
        return $request->url() === expectedNewUrl($newUrl, $this->remoteHost)
            && $request->method() === strtoupper($method)
            && collect($this->remoteHost->headers)->every(fn ($value, $key) => $request->hasHeader($key, $value));
    });
})->with([
    'folders index' => ['get', 'log-viewer.folders'],
    'folder download' => ['get', 'log-viewer.folders.request-download', ['folderIdentifier' => 'folder']],
    'folder clear cache' => ['post', 'log-viewer.folders.clear-cache', ['folderIdentifier' => 'folder']],
    'folder delete' => ['delete', 'log-viewer.folders.delete', ['folderIdentifier' => 'folder']],

    'files index' => ['get', 'log-viewer.files'],
    'file download' => ['get', 'log-viewer.files.request-download', ['fileIdentifier' => 'file']],
    'file clear cache' => ['post', 'log-viewer.files.clear-cache', ['fileIdentifier' => 'file']],
    'file delete' => ['delete', 'log-viewer.files.delete', ['fileIdentifier' => 'file']],

    'logs index' => ['get', 'log-viewer.logs'],

    'clear cache all' => ['post', 'log-viewer.files.clear-cache-all'],
    'delete multiple files' => ['post', 'log-viewer.files.delete-multiple-files'],
]);
