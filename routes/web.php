<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Opcodes\LogViewer\Facades\LogViewer;

Route::middleware(LogViewer::getRouteMiddleware())
    ->prefix(LogViewer::getRoutePrefix())
    ->group(function () {
        Route::get('/', function () {
            LogViewer::auth();

            return view('log-viewer::index', [
                'jsPath' => __DIR__.'/../public/app.js',
                'cssPath' => __DIR__.'/../public/app.css',
                'selectedFileName' => request()->query('file', ''),
            ]);
        })->name('blv.index');

        Route::get('file/{fileName}/download', function (string $fileName) {
            $file = LogViewer::getFile($fileName);

            abort_if(is_null($file), 404);

            Gate::authorize('downloadLogFile', $file);

            return $file->download();
        })->name('blv.download-file');
    });
