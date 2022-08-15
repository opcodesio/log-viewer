<?php

namespace Opcodes\LogViewer\Exceptions;

use Exception;

class InvalidRegularExpression extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $error_definitions = [
            PREG_NO_ERROR => 'Code 0: No errors',
            PREG_INTERNAL_ERROR => 'Code 1: There was an internal PCRE error',
            PREG_BACKTRACK_LIMIT_ERROR => 'Code 2: Backtrack limit was exhausted',
            PREG_RECURSION_LIMIT_ERROR => 'Code 3: Recursion limit was exhausted',
            PREG_BAD_UTF8_ERROR => 'Code 4: The offset didn\'t correspond to the begin of a valid UTF-8 code point',
            PREG_BAD_UTF8_OFFSET_ERROR => 'Code 5: Malformed UTF-8 data',
            PREG_JIT_STACKLIMIT_ERROR => 'Code 6: PCRE function failed due to limited JIT stack space',
        ];
        $last_error = preg_last_error();

        $message = $message ?: 'Search query is not a valid regular expression. ';

        if (isset($error_definitions[$last_error])) {
            $message .= $error_definitions[$last_error];
        }

        parent::__construct($message, $code, $previous);
    }
}
