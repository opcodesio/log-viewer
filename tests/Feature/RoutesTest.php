<?php

use function Pest\Laravel\get;

test('the default url can be changed', function () {
    config()->set('log-viewer.route_path', 'new-log-route');

    reloadRoutes();

    expect(route('log-viewer.index'))->toContain('new-log-route');
});

test('a domain can be set', function () {
    config()->set('log-viewer.route_domain', 'logs.domain.test');
    config()->set('log-viewer.route_path', '/');

    reloadRoutes();

    expect(route('log-viewer.index'))->toBe('http://logs.domain.test');
});

test('a domain is optional', function () {
    config()->set('log-viewer.route_path', '/');

    reloadRoutes();

    expect(route('log-viewer.index'))->toBe('http://localhost');
});

test('only use api', function () {
    config()->set('log-viewer.api_only', true);

    reloadRoutes();

    get(route('log-viewer.index'))->assertStatus(404);
});

test('only both api and web', function () {
    config()->set('log-viewer.api_only', false);

    reloadRoutes();

    expect(route('log-viewer.index'))->toBe('http://localhost/log-viewer');
    get(route('log-viewer.index'))->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function reloadRoutes(): void
{
    // unset any routes that were set previously
    app('router')->setRoutes(new \Illuminate\Routing\RouteCollection);

    // boot the service provider to register the routes again
    (new \Opcodes\LogViewer\LogViewerServiceProvider(app()))->boot();
}
