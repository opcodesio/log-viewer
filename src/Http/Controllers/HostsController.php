<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Resources\LogViewerHostResource;

class HostsController
{
    public function index()
    {
        return LogViewerHostResource::collection(
            LogViewer::getHosts()
        );
    }
}
