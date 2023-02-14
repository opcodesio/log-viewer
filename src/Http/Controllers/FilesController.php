<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Resources\LogFileResource;

class FilesController
{
    public function index(Request $request)
    {
        LogViewer::auth();

        JsonResource::withoutWrapping();

        $files = LogViewer::getFiles();

        if ($request->query('direction', 'desc') === 'asc') {
            $files = $files->sortByEarliestFirst();
        } else {
            $files = $files->sortByLatestFirst();
        }

        return LogFileResource::collection($files);
    }

    public function download(string $fileIdentifier)
    {
        LogViewer::auth();

        $file = LogViewer::getFile($fileIdentifier);

        abort_if(is_null($file), 404);

        Gate::authorize('downloadLogFile', $file);

        return $file->download();
    }

    public function clearCache(string $fileIdentifier)
    {
        LogViewer::auth();

        $file = LogViewer::getFile($fileIdentifier);

        abort_if(is_null($file), 404);

        $file->clearCache();

        return response()->json([
            'success' => true,
        ]);
    }

    public function clearCacheAll()
    {
        LogViewer::auth();

        LogViewer::getFiles()->each->clearCache();

        return response()->json([
            'success' => true,
        ]);
    }

    public function destroy(string $fileIdentifier)
    {
        LogViewer::auth();

        $file = LogViewer::getFile($fileIdentifier);

        abort_if(is_null($file), 404);

        Gate::authorize('deleteLogFile', $file);

        $file->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function deleteMultipleFiles(Request $request)
    {
        LogViewer::auth();

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
