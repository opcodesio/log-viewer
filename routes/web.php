<?php

use Illuminate\Support\Facades\Route;
use Opcodes\LogViewer\Http\Controllers\DownloadFileController;
use Opcodes\LogViewer\Http\Controllers\DownloadFolderController;
use Opcodes\LogViewer\Http\Controllers\IndexController;
use Opcodes\LogViewer\Http\Controllers\IsScanRequiredController;
use Opcodes\LogViewer\Http\Controllers\ScanFilesController;
use Opcodes\LogViewer\Http\Controllers\SearchProgressController;

Route::prefix('api')->group(function () {
    Route::get('folders', 'LogFileController@folders')->name('log-viwer.folders');

    Route::get('file/{fileIdentifier}/download', DownloadFileController::class)->name('blv.download-file');
    Route::get('folder/{folderIdentifier}/download', DownloadFolderController::class)->name('blv.download-folder');

    Route::get('is-scan-required', IsScanRequiredController::class)->name('blv.is-scan-required');
    Route::get('scan-files', ScanFilesController::class)->name('blv.scan-files');

    Route::get('search-progress', SearchProgressController::class)->name('blv.search-more');
});

// Catch all route
Route::get('/{view?}', IndexController::class)->where('view', '(.*)')->name('log-viewer.index');
