<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Opcodes\LogViewer\LogInterface
 * @mixin \Opcodes\LogViewer\BaseLog
 */
class BaseLogResource extends JsonResource
{
    public function toArray($request): array
    {
        $level = $this->getLevel();

        return [
            'index' => $this->index,
            'file_identifier' => $this->fileIdentifier,
            'file_position' => $this->filePosition,

            'level' => $level->value,
            'level_name' => $level->getName(),
            'level_class' => $level->getClass(),

            'datetime' => $this->datetime?->toDateTimeString(),
            'time' => $this->datetime?->format('H:i:s'),
            'message' => $this->message,
            'context' => $this->context,

            'full_text' => $this->getOriginalText(),
            'url' => $this->url(),
        ];
    }
}
