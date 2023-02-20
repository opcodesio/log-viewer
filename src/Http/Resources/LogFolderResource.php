<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class LogFolderResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'identifier' => $this->identifier,
            'path' => $this->path,
            'clean_path' => $this->cleanPath(),
            'is_root' => $this->isRoot(),
            'earliest_timestamp' => $this->earliestTimestamp(),
            'latest_timestamp' => $this->latestTimestamp(),
            'download_url' => $this->downloadUrl(),

            'files' => LogFileResource::collection($this->files()),

            'can_download' => Gate::check('downloadLogFolder', $this->resource),
            'can_delete' => Gate::check('deleteLogFolder', $this->resource),
            'loading' => false, // helper for frontend
        ];
    }
}
