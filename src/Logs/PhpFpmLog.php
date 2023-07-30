<?php

namespace Opcodes\LogViewer\Logs;

class PhpFpmLog extends BaseLog
{
    public static string $name = 'PHP-FPM';
    public static string $regex = '/\[(?<datetime>[^\]]+)\] (?<level>\S+): (?<message>.*)/';
}
