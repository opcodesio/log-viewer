<?php

use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;
use Opcodes\LogViewer\Http\Middleware\ForwardRequestToHostMiddleware;
use Opcodes\LogViewer\Http\Middleware\JsonResourceWithoutWrappingMiddleware;

Route::get('hosts', 'HostsController@index')->name('log-viewer.hosts');

Route::middleware([
    ForwardRequestToHostMiddleware::class,
    JsonResourceWithoutWrappingMiddleware::class,
])->group(function () {
    Route::get('folders', 'FoldersController@index')->name('log-viewer.folders');
    Route::get('folders/{folderIdentifier}/download/request', 'FoldersController@requestDownload')->name('log-viewer.folders.request-download');
    Route::post('folders/{folderIdentifier}/clear-cache', 'FoldersController@clearCache')->name('log-viewer.folders.clear-cache');
    Route::delete('folders/{folderIdentifier}', 'FoldersController@delete')->name('log-viewer.folders.delete');

    Route::get('files', 'FilesController@index')->name('log-viewer.files');
    Route::get('files/{fileIdentifier}/download/request', 'FilesController@requestDownload')->name('log-viewer.files.request-download');
    Route::post('files/{fileIdentifier}/clear-cache', 'FilesController@clearCache')->name('log-viewer.files.clear-cache');
    Route::delete('files/{fileIdentifier}', 'FilesController@delete')->name('log-viewer.files.delete');

    Route::post('clear-cache-all', 'FilesController@clearCacheAll')->name('log-viewer.files.clear-cache-all');
    Route::post('delete-multiple-files', 'FilesController@deleteMultipleFiles')->name('log-viewer.files.delete-multiple-files');

    Route::get('logs', 'LogsController@index')->name('log-viewer.logs');
});

Route::get('folders/{folderIdentifier}/download', 'FoldersController@download')
    ->middleware(ValidateSignature::class)
    ->name('log-viewer.folders.download');

Route::get('files/{fileIdentifier}/download', 'FilesController@download')
    ->middleware(ValidateSignature::class)
    ->name('log-viewer.files.download');
