<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Opcodes\LogViewer\Logs\Log
 */
class LogResource extends JsonResource
{
    public bool $preserveKeys = true;

    public function toArray($request): array
    {
        $level = $this->getLevel();
        $excludeFullText = $request->boolean('exclude_full_text', false);

        $data = [
            'index' => $this->index,
            'file_identifier' => $this->fileIdentifier,
            'file_position' => $this->filePosition,

            'level' => $level->value,
            'level_name' => $level->getName(),
            'level_class' => $level->getClass()->value,

            'datetime' => $this->datetime?->toDateTimeString(),
            'time' => $this->datetime?->format('H:i:s'),
            'message' => $this->message,
            'context' => $this->context,
            'extra' => $this->extra,

            'url' => $this->url(),
        ];

        if (! $excludeFullText) {
            $data['full_text'] = $this->getOriginalText();
        }

        return $data;
    }
}
