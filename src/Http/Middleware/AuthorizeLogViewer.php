<?php

namespace Opcodes\LogViewer\Http\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;

class AuthorizeLogViewer
{
    public function handle($request, $next)
    {
        if (
            config('log-viewer.require_auth_in_production', false)
            && App::isProduction()
            && ! Gate::has('viewLogViewer')
            && ! LogViewer::hasAuthCallback()
        ) {
            abort(403);
        }

        LogViewer::auth();

        return $next($request);
    }
}
