<?php

use Arukompas\BetterLogViewer\FileListReader;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('logs', function (FileListReader $fileReader) {
    return view('better-log-viewer::index', [
        'files' => $fileReader->getFiles(),
    ]);
    // $files = $logReader->getFiles();
    //
    // $file = $files[0];
    // $logs = $file->logs()->skip(100)->get(10);
    //
    // return response()->json([
    //     'files' => $files,
    //     'logs' => $logs,
    //     // 'log' => $log,
    //     'count' => count($logs ?? []),
    //     'success' => true,
    // ]);
});
