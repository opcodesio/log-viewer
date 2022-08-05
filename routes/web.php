<?php

use Arukompas\BetterLogViewer\FileListReader;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('logs', function (FileListReader $fileReader) {
    return view('better-log-viewer::index', [
        'files' => $fileReader->getFiles(),
        'jsPath' => __DIR__.'/../public/app.js',
        'cssPath' => __DIR__.'/../public/app.css',
    ]);
});

Route::middleware('web')->get('test', function () {
    $fileName = 'laravel-2022-07-28.log';

    $file = (new Arukompas\BetterLogViewer\FileListReader())->getFiles()->firstWhere('name', $fileName);

    $query = "/ExportUserWaitingInvoicesToDanmark/i";

    $result = preg_grep($query, file($file->path));
    $file->logs()->scan(true);
});
