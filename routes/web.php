<?php

use Opcodes\LogViewer\Facades\LogViewer;
use Illuminate\Support\Facades\Route;

Route::middleware(LogViewer::getRouteMiddleware())
    ->prefix(LogViewer::getRoutePrefix())
    ->group(function () {
        Route::get('/', function () {
            return view('log-viewer::index', [
                'jsPath' => __DIR__.'/../public/app.js',
                'cssPath' => __DIR__.'/../public/app.css',
            ]);
        })->name('blv.index');
    });
