<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Opcodes\LogViewer\HttpAccessLog;

/**
 * @mixin HttpAccessLog
 */
class HttpAccessLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'index' => $this->filePosition,
            'file_identifier' => $this->fileIdentifier,
            'file_position' => $this->filePosition,

            'ip' => $this->ip ?? null,
            'identity' => $this->identity ?? null,
            'remote_user' => $this->remoteUser ?? null,
            'datetime' => $this->datetime?->toDateTimeString() ?? null,
            'method' => strtoupper($this->method) ?? null,
            'path' => $this->path ?? null,
            'http_version' => $this->httpVersion ?? null,
            'status_code' => $this->statusCode ?? null,
            'content_length' => $this->contentLength ?? null,
            'referrer' => $this->referrer ?? null,
            'user_agent' => $this->userAgent ?? null,
            'url' => $this->url(),
        ];
    }
}
