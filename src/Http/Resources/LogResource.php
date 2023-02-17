<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'index' => $this->index,
            'datetime' => $this->time?->toDateTimeString() ?? null,
            'time' => $this->time?->format('H:i:s') ?? null,
            'level' => $this->level->value,
            'level_name' => $this->level->getName(),
            'level_class' => $this->level->getClass(),
            'environment' => $this->environment,
            'text' => $this->text,
            'full_text' => $this->fullText,
            'full_text_incomplete' => $this->fullTextIncomplete,
            'full_text_length' => $this->fullTextLength,
            'full_text_length_formatted' => $this->fullTextLengthFormatted(),
            'file_identifier' => $this->fileIdentifier,
            'file_position' => $this->filePosition,
            'url' => $this->url(),
        ];
    }
}
