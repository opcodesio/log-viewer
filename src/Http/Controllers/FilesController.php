<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;

class FilesController
{
    public function download(string $fileIdentifier)
    {
        LogViewer::auth();

        $file = LogViewer::getFile($fileIdentifier);

        abort_if(is_null($file), 404);

        Gate::authorize('downloadLogFile', $file);

        return $file->download();
    }
}
