<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogFile;

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

        Route::get('requires-scan', function () {
            $files = LogViewer::getFiles();
            $filesRequiringScans = $files->filter(fn (LogFile $file) => $file->logs()->requiresScan());
            $totalFileSize = $filesRequiringScans->sum(fn (LogFile $file) => $file->logs()->numberOfNewBytes());
            $estimatedSecondsToScan = 0;

            if ($filesRequiringScans->isNotEmpty()) {
                // Let's estimate the scan duration by sampling the speed of the first scan.
                // For more accurate results, let's scan a file that's more than 10 MB in size.
                $file = $filesRequiringScans->filter(fn ($file) => $file->sizeInMB() > 10)->first();

                if (is_null($file)) {
                    $file = $filesRequiringScans->sortByDesc(fn ($file) => $file->size())->first();
                }

                $scanStart = microtime(true);
                $file->logs()->scan();
                $scanEnd = microtime(true);

                // because we already scanned it here, it won't need to be scanned later.
                $totalFileSize -= $file->size();

                $durationInMicroseconds = ($scanEnd - $scanStart) * 1000_000;
                $microsecondsPerMB = $durationInMicroseconds / $file->sizeInMB() * 1.20; // 20% buffer just in case
                $totalFileSizeInMB = $totalFileSize / 1024 / 1024;

                $estimatedSecondsToScan = ceil($totalFileSizeInMB * $microsecondsPerMB / 1000_000);
            }

            $estimatedTimeForHumans = \Carbon\CarbonInterval::seconds($estimatedSecondsToScan)->cascade()->forHumans();

            return response()->json([
                'requires_scan' => $filesRequiringScans->isNotEmpty(),
                'estimated_scan_seconds' => $estimatedSecondsToScan,
                'estimated_scan_human' => $estimatedTimeForHumans,
            ]);
        })->name('blv.requires-scan');

        Route::get('scan-files', function () {
            $files = LogViewer::getFiles();
            $filesRequiringScans = $files->filter(fn (LogFile $file) => $file->logs()->requiresScan());

            $filesRequiringScans->each(function (LogFile $file) {
                $file->logs()->scan();

                \Opcodes\LogViewer\LogReader::clearInstance($file);
            });

            return response()->json([
                'success' => true,
            ]);
        })->name('blv.scan-files');
    });
