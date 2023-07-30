<?php

namespace Opcodes\LogViewer\Logs;

class PhpFpmLog extends Log
{
    public static string $name = 'PHP-FPM';
    public static string $regex = '/\[(?<datetime>[^\]]+)\] (?<level>\S+): (?<message>.*)/';
}
