<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class LogFileResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'identifier' => $this->identifier,
            'sub_folder' => $this->subFolder,
            'sub_folder_identifier' => $this->subFolderIdentifier(),
            'path' => $this->path,
            'name' => $this->name,
            'size' => $this->size(),
            'size_in_mb' => $this->sizeInMB(),
            'size_formatted' => $this->sizeFormatted(),
            'download_url' => $this->downloadUrl(),
            'earliest_timestamp' => $this->earliestTimestamp(),
            'latest_timestamp' => $this->latestTimestamp(),

            'can_download' => Gate::check('downloadLogFile', $this->resource),
            'can_delete' => Gate::check('deleteLogFile', $this->resource),

            'loading' => false, // helper for frontend
            'selected_for_deletion' => false, // helper for frontend
        ];
    }
}
