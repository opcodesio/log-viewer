<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Controllers\IsScanRequiredController;
use Opcodes\LogViewer\Http\Controllers\ScanFilesController;

Route::domain(LogViewer::getRouteDomain())
    ->middleware(LogViewer::getRouteMiddleware())
    ->prefix(LogViewer::getRoutePrefix())
    ->group(function () {
        Route::get('/', function () {
            LogViewer::auth();

            $selectedFile = LogViewer::getFile(request()->query('file', ''));

            return view('log-viewer::index', [
                'jsPath' => __DIR__.'/../public/app.js',
                'cssPath' => __DIR__.'/../public/app.css',
                'selectedFile' => $selectedFile,
            ]);
        })->name('blv.index');

        Route::get('file/{fileIdentifier}/download', function (string $fileIdentifier) {
            LogViewer::auth();

            $file = LogViewer::getFile($fileIdentifier);

            abort_if(is_null($file), 404);

            Gate::authorize('downloadLogFile', $file);

            return $file->download();
        })->name('blv.download-file');

        Route::get('is-scan-required', IsScanRequiredController::class)->name('blv.is-scan-required');
        Route::get('scan-files', ScanFilesController::class)->name('blv.scan-files');
    });
