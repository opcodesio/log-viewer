<?php

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use function Pest\Laravel\get;

test('testing route', function ($route) {
    get(route($route))->assertOK();
})->with([
    'blv.index',
]);

test('the default url can be changed', function () {
    config()->set('log-viewer.route_path', 'new-log-route');

    reloadRoutes();

    expect(route('blv.index'))->toContain('new-log-route');

    get(route('blv.index'))->assertOK();
});

test('a domain can be set', function () {
    config()->set('log-viewer.route_domain', 'logs.domain.test');
    config()->set('log-viewer.route_path', '/');

    reloadRoutes();

    expect(route('blv.index'))->toBe('http://logs.domain.test');

    get(route('blv.index'))->assertOK();
});

test('a domain is optional', function () {
    config()->set('log-viewer.route_path', '/');

    reloadRoutes();

    expect(route('blv.index'))->toBe('http://localhost');

    get(route('blv.index'))->assertOk();
});

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function reloadRoutes(): void
{
    $router = Route::getFacadeRoot();
    $router->setRoutes((new RouteCollection()));

    Route::group([], 'routes/web.php');
}
