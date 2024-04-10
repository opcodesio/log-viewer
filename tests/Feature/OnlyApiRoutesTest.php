<?php

use Opcodes\LogViewer\LogViewerServiceProvider;
use Opcodes\LogViewer\Tests\DeferPackageProviderTestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

uses(DeferPackageProviderTestCase::class);

test('only has api routes', function () {
    config(['log-viewer.api_only' => true]);
    $this->app->register(LogViewerServiceProvider::class);
    route('log-viewer.index');
})->throws(RouteNotFoundException::class);
