<?php

namespace Opcodes\LogViewer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;

class ForwardRequestToHostMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $query = $request->query();
        $hostIdentifier = $query['host'] ?? '';
        unset($query['host']);
        $host = LogViewer::getHost($hostIdentifier);

        if ($host) {
            $actionPath = Str::replaceFirst(config('log-viewer.route_path'), '', $request->path());
            $url = $host->host.$actionPath . (!empty($query) ? '?'.http_build_query($query) : '');

            return Http::withHeaders($host->headers ?? [])->send($request->method(), $url);
        }

        return $next($request);
    }
}
