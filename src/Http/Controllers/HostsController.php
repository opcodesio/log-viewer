<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Http\Resources\LogViewerHostResource;

class HostsController
{
    public function index()
    {
        LogViewer::auth();

        return LogViewerHostResource::collection(
            LogViewer::getHosts()
        );
    }

    public function healthCheck(string $hostIdentifier)
    {
        LogViewer::auth();

        $host = LogViewer::getHost($hostIdentifier);

        return LogViewerHostResource::make($host);
    }
}
