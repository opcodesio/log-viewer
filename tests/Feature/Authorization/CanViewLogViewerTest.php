<?php

use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

test('can define an "auth" callback for authorization', function () {
    get(route('log-viewer.index'))->assertOk();

    // with the gate defined and a false value, it should not be possible to access the log viewer
    LogViewer::auth(fn ($request) => false);
    get(route('log-viewer.index'))->assertForbidden();

    // now let's give them access
    LogViewer::auth(fn ($request) => true);
    get(route('log-viewer.index'))->assertOk();
});

test('the "auth" callback is given with a Request object to check against', function () {
    LogViewer::auth(function ($request) {
        expect($request)->toBeInstanceOf(\Illuminate\Http\Request::class);

        return true;
    });

    get(route('log-viewer.index'))->assertOk();
});

test('can define a "viewLogViewer" gate as an alternative', function () {
    get(route('log-viewer.index'))->assertOk();

    Gate::define('viewLogViewer', fn ($user = null) => false);
    get(route('log-viewer.index'))->assertForbidden();

    Gate::define('viewLogViewer', fn ($user = null) => true);
    get(route('log-viewer.index'))->assertOk();
});

test('local environment can use Log Viewer by default', function () {
    app()->detectEnvironment(fn () => 'local');
    expect(app()->isProduction())->toBeFalse();

    get(route('log-viewer.index'))->assertOk();
});

test('Log Viewer is blocked in production environment by default', function () {
    app()->detectEnvironment(fn () => 'production');
    expect(app()->isProduction())->toBeTrue();

    get(route('log-viewer.index'))->assertForbidden();

    // but if configuration allows...
    config(['log-viewer.require_auth_in_production' => false]);
    get(route('log-viewer.index'))->assertOk();
});

test('Log Viewer is not blocked if the Log Viewer auth middleware is not used', function () {
    config(['log-viewer.middleware' => ['web']]);
    app()->detectEnvironment(fn () => 'production');
    expect(app()->isProduction())->toBeTrue();
    // need to reload the routes in order for the new middleware to take place.
    (new \Opcodes\LogViewer\LogViewerServiceProvider(app()))->boot();

    get(route('log-viewer.index'))->assertOk();
});

test('auth callback works consistently for both web and API routes', function () {
    $webCalls = 0;
    $apiCalls = 0;

    LogViewer::auth(function ($request) use (&$webCalls, &$apiCalls) {
        if ($request->is('log-viewer/api/*')) {
            $apiCalls++;
        } else {
            $webCalls++;
        }

        return true;
    });

    // Access web route
    get(route('log-viewer.index'))->assertOk();
    expect($webCalls)->toBe(1);

    // Access API route
    getJson(route('log-viewer.folders'), ['referer' => 'http://localhost/'])->assertOk();
    expect($apiCalls)->toBe(1);
});
