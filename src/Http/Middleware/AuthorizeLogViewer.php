<?php

namespace Opcodes\LogViewer\Http\Middleware;

use Opcodes\LogViewer\Facades\LogViewer;

class AuthorizeLogViewer
{
    public function handle($request, $next)
    {
        LogViewer::auth();

        return $next($request);
    }
}
