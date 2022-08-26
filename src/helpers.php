<?php

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
