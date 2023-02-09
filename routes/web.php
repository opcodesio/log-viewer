<?php

use Arukompas\BetterLogViewer\FileListReader;
use Arukompas\BetterLogViewer\LogFile;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('logs', function (FileListReader $fileReader) {
    return view('better-log-viewer::index', [
        'files' => $fileReader->getFiles(),
        'jsPath' => __DIR__.'/../public/app.js',
        'cssPath' => __DIR__.'/../public/app.css',
    ]);
})->name('blv.index');

Route::middleware('web')->prefix('logs')->group(function () {
    Route::get('files', function () {
        return (new FileListReader())->getFiles()
            ->map(function (LogFile $file) {
                return [
                    'name' => $file->name,
                    'size_formatted' => $file->sizeFormatted(),
                    'download_url' => route('blv.file.download', $file->name),
                ];
            });
    });
    Route::get('file/{file}/logs', function ($file) {
        //
    });
    Route::get('file/{file}/download', fn ($file) => FileListReader::findByName($file)?->download())->name('blv.file.download');
    Route::delete('file/{file}', fn ($file) => FileListReader::findByName($file)?->delete());
});

Route::middleware('web')->get('logs-new', function () {
    /** TODO: remove this before publishing */
    \Illuminate\Support\Facades\Artisan::call('vendor:publish', [
        '--tag' => 'better-log-viewer-assets',
        '--force' => 1,
    ]);

    return view('better-log-viewer::vue');
});
//
// Route::middleware('web')->get('test', function () {
//     $fileName = 'laravel-2022-07-28.log';
//
//     $file = (new Arukompas\BetterLogViewer\FileListReader())->getFiles()->firstWhere('name', $fileName);
//
//     // $query = "/ExportUserWaitingInvoicesToDanmark/i";
//
//     $logs = $file->logs()->reverse()->paginate(50);
//
//     return $logs;
// });
