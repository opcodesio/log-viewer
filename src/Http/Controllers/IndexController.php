<?php

namespace Opcodes\LogViewer\Http\Controllers;

use Opcodes\LogViewer\Facades\LogViewer;

class IndexController
{
    public function __invoke()
    {
        LogViewer::auth();

        return view('log-viewer::index', [
            'assetsAreCurrent' => LogViewer::assetsAreCurrent(),
            'logViewerScriptVariables' => [
                'path' => config('log-viewer.route_path'),
            ],
        ]);
    }
}
