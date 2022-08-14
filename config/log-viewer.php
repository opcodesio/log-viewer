<?php

return [
    /**
     * Log Viewer route path.
     */
    'route_path' => 'logs',

    /**
     * When set, displays a link to easily get back to this URL.
     * Set to `null` to hide this link.
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
];
