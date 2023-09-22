<?php

namespace Opcodes\LogViewer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JsonResourceWithoutWrappingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $originalWrappingBehaviour = JsonResource::$wrap;

        JsonResource::withoutWrapping();

        $response = $next($request);

        JsonResource::wrap($originalWrappingBehaviour);

        return $response;
    }
}
