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
            $url = $host->host.$actionPath.(! empty($query) ? '?'.http_build_query($query) : '');
            $headers = array_merge([
                'X-Forwarded-Host' => $request->getHost(),
                'X-Forwarded-Port' => $request->getPort(),
                'X-Forwarded-Proto' => $request->getScheme(),
            ], $host->headers ?? []);

            $proxyRequest = Http::withHeaders($headers)->acceptJson();

            if ($host->auth && isset($host->auth['token'])) {
                $proxyRequest = $proxyRequest->withToken($host->auth['token']);
            } elseif ($host->auth && isset($host->auth['username']) && isset($host->auth['password'])) {
                $proxyRequest = $proxyRequest->withBasicAuth($host->auth['username'], $host->auth['password']);
            } elseif ($host->auth && isset($host->auth['digest'])) {
                $proxyRequest = $proxyRequest->withDigestAuth($host->auth['username'], $host->auth['password']);
            }

            if (! $host->verifyServerCertificate) {
                $proxyRequest = $proxyRequest->withoutVerifying();
            }

            $response = $proxyRequest->send($request->method(), $url);

            return response(
                $response->body(),
                $response->status(),
                [
                    'Content-Type' => $response->header('Content-Type'),
                ]
            );
        }

        return $next($request);
    }
}
