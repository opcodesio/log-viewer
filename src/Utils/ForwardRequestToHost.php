<?php

namespace Opcodes\LogViewer\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Opcodes\LogViewer\Facades\LogViewer;

class ForwardRequestToHost
{
    public static function forward(Request $request): mixed
    {
        $query = $request->query();
        $hostIdentifier = $query['host'];
        unset($query['host']);
        $host = LogViewer::getHost($hostIdentifier);

        if (! $host) {
            abort(404, 'Host configuration not found.');
        }

        $actionPath = Str::replaceFirst(config('log-viewer.route_path'), '', $request->path());

        $response = Http::withHeaders($host->headers ?? [])
            ->get($host->host . $actionPath, http_build_query($query));

        return $response->json();
    }
}
