<?php

use Arukompas\BetterLogViewer\Facades\BetterLogViewer;
use Illuminate\Support\Facades\Route;

Route::middleware(BetterLogViewer::getRouteMiddleware())
    ->prefix(BetterLogViewer::getRoutePrefix())
    ->group(function () {
        Route::get('/', function () {
            return view('better-log-viewer::index', [
                'jsPath' => __DIR__.'/../public/app.js',
                'cssPath' => __DIR__.'/../public/app.css',
            ]);
        })->name('blv.index');
    });
