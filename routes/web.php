<?php

use Illuminate\Support\Facades\Route;
use Opcodes\LogViewer\Facades\LogViewer;

Route::middleware(LogViewer::getRouteMiddleware())
    ->prefix(LogViewer::getRoutePrefix())
    ->group(function () {
        Route::get('/', function () {
            return view('log-viewer::index', [
                'jsPath' => __DIR__.'/../public/app.js',
                'cssPath' => __DIR__.'/../public/app.css',
                'selectedFileName' => request()->query('file', ''),
            ]);
        })->name('blv.index');
    });
