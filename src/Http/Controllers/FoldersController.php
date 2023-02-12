<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Resources\LogFolderResource;

class FoldersController
{
    public function index(Request $request)
    {
        JsonResource::withoutWrapping();

        $folders = LogViewer::getFilesGroupedByFolder();

        if ($request->query('direction', 'desc') === 'asc') {
            $folders = $folders->sortByEarliestFirstIncludingFiles();
        } else {
            $folders = $folders->sortByLatestFirstIncludingFiles();
        }

        return LogFolderResource::collection($folders);
    }

    public function download(string $folderIdentifier)
    {
        LogViewer::auth();

        $folder = LogViewer::getFolder($folderIdentifier);

        abort_if(is_null($folder), 404);

        Gate::authorize('downloadLogFolder', $folder);

        return $folder->download();
    }
}
