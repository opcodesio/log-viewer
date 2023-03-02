<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LogViewerHostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'host' => $this->host,
            'headers' => $this->headers ?? [],
            'auth' => $this->auth ?? [],
            'is_remote' => $this->is_remote,
        ];
    }
}
