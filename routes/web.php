<?php

use Arukompas\BetterLogViewer\LogReader;
use Illuminate\Support\Facades\Route;

Route::get('logs', function (LogReader $logReader) {
    $files = $logReader->getFiles();

    $logs = [];
    $file = $files[2];
    $logs = $file->getLogs();
    // $logs = [$file->nextLog()];

    // $logs = $file->getLogs();

    return response()->json([
        // 'contents' => $contents,
        // 'files' => $files,
        // 'logs' => $logs,
        // 'log' => $log,
        'count' => count($logs),
        'success' => true,
    ]);
});
