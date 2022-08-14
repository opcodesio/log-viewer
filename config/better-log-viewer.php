<?php

return [
    /**
     * Log Viewer route path.
     */
    'route_path' => 'log-viewer',

    /**
     * When set, displays a link to easily get back to this URL.
     */
    'back_to_system_url' => config('app.url', null),

    /**
     * Optional label to display for the above URL. Defaults to "Back to {{ app.name }}"
     */
    'back_to_system_label' => null,

    /**
     * Log Viewer route middleware
     */
    'middleware' => [],

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
];
