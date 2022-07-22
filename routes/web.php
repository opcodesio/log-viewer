<?php

use Arukompas\BetterLogViewer\FileListReader;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('logs', function (FileListReader $fileReader) {
    return view('better-log-viewer::index', [
        'files' => $fileReader->getFiles(),
        'cssPath' => __DIR__.'/../public/app.css',
    ]);
});
