<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Resources\LogFolderResource;

class LogFileController
{
    public function folders()
    {
        JsonResource::withoutWrapping();

        return LogFolderResource::collection(
            LogViewer::getFilesGroupedByFolder()
        );
    }
}
