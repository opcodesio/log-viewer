<?php

namespace Opcodes\LogViewer\Utils;

use Opcodes\LogViewer\Exceptions\InvalidRegularExpression;

class Utils
{
    /**
     * Get a human-friendly readable string of the number of bytes provided.
     */
    public static function bytesForHumans(int $bytes): string
    {
        if ($bytes > ($gb = 1024 * 1024 * 1024)) {
            return number_format($bytes / $gb, 2).' GB';
        } elseif ($bytes > ($mb = 1024 * 1024)) {
            return number_format($bytes / $mb, 2).' MB';
        } elseif ($bytes > ($kb = 1024)) {
            return number_format($bytes / $kb, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Calculate the memory footprint of a given variable.
     * CAUTION: This will increase the memory usage by that same amount because it makes a copy of this variable.
     */
    public static function sizeOfVar(mixed $var): int
    {
        $start_memory = memory_get_usage();
        $tmp = unserialize(serialize($var));

        return memory_get_usage() - $start_memory;
    }

    /**
     * Calculate the memory footprint of a given variable and return it as a human-friendly string.
     * CAUTION: This will increase the memory usage by that same amount because it makes a copy of this variable.
     */
    public static function sizeOfVarInMB(mixed $var): string
    {
        return self::bytesForHumans(self::sizeOfVar($var));
    }

    public static function validateRegex(string $regexString, bool $throw = true): bool
    {
        $error = null;
        set_error_handler(function (int $errno, string $errstr) use (&$error) {
            $error = $errstr;
        }, E_WARNING);
        preg_match($regexString, '');
        restore_error_handler();

        if (! empty($error)) {
            $error = str_replace('preg_match(): ', '', $error);

            if ($throw) {
                throw new InvalidRegularExpression($error);
            }

            return false;
        }

        return true;
    }

    public static function shortMd5(string $content, int $length = 8): string
    {
        if ($length > 32) {
            $length = 32;
        }

        return substr(md5($content), -$length, $length);
    }
}
