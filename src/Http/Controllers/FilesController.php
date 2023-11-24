<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Resources\LogFileResource;

class FilesController
{
    public function index(Request $request)
    {
        $files = LogViewer::getFiles();

        if ($request->query('direction', 'desc') === 'asc') {
            $files = $files->sortByEarliestFirst();
        } else {
            $files = $files->sortByLatestFirst();
        }

        return LogFileResource::collection($files);
    }

    public function requestDownload(Request $request, string $fileIdentifier)
    {
        $file = LogViewer::getFile($fileIdentifier);

        abort_if(is_null($file), 404);

        Gate::authorize('downloadLogFile', $file);

        return response()->json([
            'url' => URL::temporarySignedRoute(
                'log-viewer.files.download',
                now()->addMinute(),
                ['fileIdentifier' => $fileIdentifier]
            ),
        ]);
    }

    public function download(string $fileIdentifier)
    {
        $file = LogViewer::getFile($fileIdentifier);

        return $file->download();
    }

    public function clearCache(string $fileIdentifier)
    {
        $file = LogViewer::getFile($fileIdentifier);

        abort_if(is_null($file), 404);

        $file->clearCache();

        return response()->json([
            'success' => true,
        ]);
    }

    public function clearCacheAll()
    {
        LogViewer::getFiles()->each->clearCache();

        return response()->json([
            'success' => true,
        ]);
    }

    public function delete(string $fileIdentifier)
    {
        $file = LogViewer::getFile($fileIdentifier);

        if (is_null($file)) {
            return response()->json(['success' => true]);
        }

        Gate::authorize('deleteLogFile', $file);

        $file->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function deleteMultipleFiles(Request $request)
    {
        $selectedFilesArray = $request->input('files', []);

        foreach ($selectedFilesArray as $fileIdentifier) {
            $file = LogViewer::getFile($fileIdentifier);

            if (! $file || ! Gate::check('deleteLogFile', $file)) {
                continue;
            }

            $file->delete();
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
