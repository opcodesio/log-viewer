<?php

use Arukompas\BetterLogViewer\FileListReader;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix(config('better-log-viewer.route_path'))
    ->group(function () {
        Route::get('/', function (FileListReader $fileReader) {
            return view('better-log-viewer::index', [
                'files' => $fileReader->getFiles(),
                'jsPath' => __DIR__.'/../public/app.js',
                'cssPath' => __DIR__.'/../public/app.css',
            ]);
        })->name('blv.index');
    });

Route::middleware('web')->get('test', function () {
    $fileName = 'laravel-2022-07-28.log';

    $file = (new Arukompas\BetterLogViewer\FileListReader())->getFiles()->firstWhere('name', $fileName);

    // $query = "/ExportUserWaitingInvoicesToDanmark/i";

    $logs = $file->logs()->reverse()->paginate(50);

    return $logs;
});
