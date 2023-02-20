<?php

namespace Opcodes\LogViewer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelCountResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'level' => $this->level->value,
            'level_name' => $this->level->getName(),
            'level_class' => $this->level->getClass(),
            'count' => $this->count,
            'selected' => $this->selected,
        ];
    }
}
