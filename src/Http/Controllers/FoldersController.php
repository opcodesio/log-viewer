<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Opcodes\LogViewer\Enums\FolderSortingMethod;
use Opcodes\LogViewer\Enums\SortingOrder;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Resources\LogFolderResource;
use Opcodes\LogViewer\LogFile;

class FoldersController
{
    public function index(Request $request)
    {
        $folders = LogViewer::getFilesGroupedByFolder();

        $sortingMethod = config('log-viewer.defaults.folder_sorting_method', FolderSortingMethod::ModifiedTime);
        $sortingOrder = config('log-viewer.defaults.folder_sorting_order', SortingOrder::Descending);

        $fileSortingOrder = $request->query('direction', 'desc');

        if ($sortingMethod === FolderSortingMethod::Alphabetical) {
            if ($sortingOrder === SortingOrder::Ascending) {
                $folders = $folders->sortAlphabeticallyAsc();
            } else {
                $folders = $folders->sortAlphabeticallyDesc();
            }

            // Still sort files inside folders by direction param
            $folders->each(function ($folder) use ($fileSortingOrder) {
                if ($fileSortingOrder === 'asc') {
                    $folder->files()->sortByEarliestFirst();
                } else {
                    $folder->files()->sortByLatestFirst();
                }
            });
        } else { // ModifiedTime
            if ($fileSortingOrder === 'asc') {
                $folders = $folders->sortByEarliestFirstIncludingFiles();
            } else {
                $folders = $folders->sortByLatestFirstIncludingFiles();
            }
        }

        return LogFolderResource::collection($folders->values());
    }

    public function requestDownload(Request $request, string $folderIdentifier)
    {
        $folder = LogViewer::getFolder($folderIdentifier);

        abort_if(is_null($folder), 404);

        Gate::authorize('downloadLogFolder', $folder);

        return response()->json([
            'url' => URL::temporarySignedRoute(
                'log-viewer.folders.download',
                now()->addMinutes(30),   // longer time to allow for processing of the ZIP file
                ['folderIdentifier' => $folderIdentifier]
            ),
        ]);
    }

    public function download(string $folderIdentifier)
    {
        $folder = LogViewer::getFolder($folderIdentifier);

        return $folder->download();
    }

    public function clearCache(string $folderIdentifier)
    {
        $folder = LogViewer::getFolder($folderIdentifier);

        abort_if(is_null($folder), 404);

        $folder?->files()->each->clearCache();

        return response()->json(['success' => true]);
    }

    public function delete(string $folderIdentifier)
    {
        $folder = LogViewer::getFolder($folderIdentifier);

        if (is_null($folder)) {
            return response()->json(['success' => true]);
        }

        Gate::authorize('deleteLogFolder', $folder);

        $folder->files()->each(function (LogFile $file) {
            if (Gate::check('deleteLogFile', $file)) {
                $file->delete();
            }
        });

        return response()->json(['success' => true]);
    }
}
