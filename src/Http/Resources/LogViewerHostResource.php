<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogViewerHostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'host' => $this->host,
            'headers' => $this->headers,
        ];
    }
}
