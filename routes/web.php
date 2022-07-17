<?php

use Arukompas\BetterLogViewer\FileListReader;
use Illuminate\Support\Facades\Route;

Route::get('logs', function (FileListReader $logReader) {
    $files = $logReader->getFiles();

    $file = $files[0];
    $logs = $file->logs()->skip(100)->get(10);

    return response()->json([
        'files' => $files,
        'logs' => $logs,
        // 'log' => $log,
        'count' => count($logs ?? []),
        'success' => true,
    ]);
});
