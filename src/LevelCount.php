<?php

namespace Opcodes\LogViewer;

class LevelCount
{
    public function __construct(
        public LevelInterface $level,
        public int $count = 0,
        public bool $selected = false,
    )
    {
    }
}
