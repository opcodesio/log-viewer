<?php

return [
    /**
     * Log Viewer route path.
     */
    'route_path' => 'log-viewer',

    /**
     * Log Viewer route middleware
     */
    'middleware' => [],

    /**
     * Log Viewer API middleware
     */
    'api_middleware' => [],

    /**
     * Include file patterns
     */
    'include_files' => ['*.log'],

    /**
     * Exclude file patterns. This will take precedence
     */
    'exclude_files' => [],

    /**
     * Shorter stack trace filters. Any lines matching these regex patters will be excluded.
     */
    'shorter_stack_trace_excludes' => [
        '/vendor/symfony/',
        '/vendor/laravel/framework/',
        '/vendor/barryvdh/laravel-debugbar/'
    ],

    'enable_cache' => true,
];
