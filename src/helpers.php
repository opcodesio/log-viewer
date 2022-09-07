<?php

const DECIMAL_LENGTH = 2,
      PACKET_SIZE = 1024;

if (! function_exists('highlight_search_result')) {
    /**
     * Highlights search query results and escapes HTML.
     * Safe to use within {!! !!} in Blade.
     *
     * @param  string  $text
     * @param  string|null  $query
     * @return string
     */
    function highlight_search_result(string $text, string $query = null): string
    {
        if (! empty($query)) {
            if (! \Illuminate\Support\Str::endsWith($query, '/i')) {
                $query = '/'.$query.'/i';
            }

            $text = preg_replace_callback(
                $query,
                function ($matches) {
                    return '<mark>'.$matches[0].'</mark>';
                },
                $text
            );
        }

        // Let's return the <mark> tags which we use for highlighting the search results
        // while escaping the rest of the HTML entities
        return str_replace(
            [htmlspecialchars('<mark>'), htmlspecialchars('</mark>')],
            ['<mark>', '</mark>'],
            htmlspecialchars($text)
        );
    }
}

if (! function_exists('bytes_formatted')) {
    /**
     * Get a human-friendly readable string of the number of bytes provided.
     */
    function bytes_formatted(int $bytes): string
    {
        $formatTypeForFileSize = floor(log($bytes) / log(PACKET_SIZE));
        
        return 0 === $bytes ? '0 bytes' :
        number_format($bytes / pow(PACKET_SIZE, $formatTypeForFileSize), max(0, DECIMAL_LENGTH)) . ' ' .
            ['Bytes', 'KB', 'MB', 'GB'][$formatTypeForFileSize];
    }
}

if (! function_exists('size_of_var')) {
    /**
     * Calculate the memory footprint of a given variable.
     * CAUTION: This will increase the memory usage by that same amount because it makes a copy of this variable.
     */
    function size_of_var(mixed $var): int
    {
        $start_memory = memory_get_usage();
        $tmp = unserialize(serialize($var));

        return memory_get_usage() - $start_memory;
    }
}

if (! function_exists('size_of_var_in_mb')) {
    /**
     * Calculate the memory footprint of a given variable and return it as a human-friendly string.
     * CAUTION: This will increase the memory usage by that same amount because it makes a copy of this variable.
     */
    function size_of_var_in_mb(mixed $var): string
    {
        return bytes_formatted(size_of_var($var));
    }
}
