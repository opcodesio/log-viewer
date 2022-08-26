<?php

use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;
use function Pest\Laravel\get;

test('can define "viewLogViewer" gate', function () {
    get(route('blv.index'))->assertOk();

    // with the gate defined and a false value, it should not be possible to access the log viewer
    LogViewer::auth(fn ($request) => false);
    get(route('blv.index'))->assertForbidden();

    // now let's give them access
    LogViewer::auth(fn ($request) => true);
    get(route('blv.index'))->assertOk();
});

test('auth callback is provided with a Request object', function () {
    LogViewer::auth(function ($request) {
        expect($request)->toBeInstanceOf(\Illuminate\Http\Request::class);

        return true;
    });

    get(route('blv.index'))->assertOk();
});
