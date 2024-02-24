<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log Viewer
    |--------------------------------------------------------------------------
    | Log Viewer can be disabled, so it's no longer accessible via browser.
    |
    */

    'enabled' => env('LOG_VIEWER_ENABLED', true),

    'require_auth_in_production' => true,

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Domain
    |--------------------------------------------------------------------------
    | You may change the domain where Log Viewer should be active.
    | If the domain is empty, all domains will be valid.
    |
    */

    'route_domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Route
    |--------------------------------------------------------------------------
    | Log Viewer will be available under this URL.
    |
    */

    'route_path' => 'log-viewer',

    /*
    |--------------------------------------------------------------------------
    | Back to system URL
    |--------------------------------------------------------------------------
    | When set, displays a link to easily get back to this URL.
    | Set to `null` to hide this link.
    |
    | Optional label to display for the above URL.
    |
    */

    'back_to_system_url' => config('app.url', null),

    'back_to_system_label' => null, // Displayed by default: "Back to {{ app.name }}"

    /*
    |--------------------------------------------------------------------------
    | Log Viewer time zone.
    |--------------------------------------------------------------------------
    | The time zone in which to display the times in the UI. Defaults to
    | the application's timezone defined in config/app.php.
    |
    */

    'timezone' => config('app.timezone', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Log Viewer route middleware.
    |--------------------------------------------------------------------------
    | Optional middleware to use when loading the initial Log Viewer page.
    |
    */

    'middleware' => [
        'web',
        \Opcodes\LogViewer\Http\Middleware\AuthorizeLogViewer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Viewer API middleware.
    |--------------------------------------------------------------------------
    | Optional middleware to use on every API request. The same API is also
    | used from within the Log Viewer user interface.
    |
    */

    'api_middleware' => [
        \Opcodes\LogViewer\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \Opcodes\LogViewer\Http\Middleware\AuthorizeLogViewer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Remote hosts.
    |--------------------------------------------------------------------------
    | Log Viewer supports viewing Laravel logs from remote hosts. They must
    | be running Log Viewer as well. Below you can define the hosts you
    | would like to show in this Log Viewer instance.
    |
    */

    'hosts' => [
        'local' => [
            'name' => ucfirst(env('APP_ENV', 'local')),
        ],

        // 'staging' => [
        //     'name' => 'Staging',
        //     'host' => 'https://staging.example.com/log-viewer',
        //     'auth' => [      // Example of HTTP Basic auth
        //         'username' => 'username',
        //         'password' => 'password',
        //     ],
        // ],
        //
        // 'production' => [
        //     'name' => 'Production',
        //     'host' => 'https://example.com/log-viewer',
        //     'auth' => [      // Example of Bearer token auth
        //         'token' => env('LOG_VIEWER_PRODUCTION_TOKEN'),
        //     ],
        //     'headers' => [
        //         'X-Foo' => 'Bar',
        //     ],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Include file patterns
    |--------------------------------------------------------------------------
    |
    */

    'include_files' => [
        '*.log',
        '**/*.log',

        // You can include paths to other log types as well, such as apache, nginx, and more.
        '/var/log/httpd/*',
        '/var/log/nginx/*',

        // MacOS Apple Silicon logs
        '/opt/homebrew/var/log/nginx/*',
        '/opt/homebrew/var/log/httpd/*',
        '/opt/homebrew/var/log/php-fpm.log',
        '/opt/homebrew/var/log/postgres*log',
        '/opt/homebrew/var/log/redis*log',
        '/opt/homebrew/var/log/supervisor*log',

        // '/absolute/paths/supported',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude file patterns.
    |--------------------------------------------------------------------------
    | This will take precedence over included files.
    |
    */

    'exclude_files' => [
        // 'my_secret.log'
    ],

    /*
    |--------------------------------------------------------------------------
    | Hide unknown files.
    |--------------------------------------------------------------------------
    | The include/exclude options above might catch files which are not
    | logs supported by Log Viewer. In that case, you can hide them
    | from the UI and API calls by setting this to true.
    |
    */

    'hide_unknown_files' => true,

    /*
    |--------------------------------------------------------------------------
    |  Shorter stack trace filters.
    |--------------------------------------------------------------------------
    | Lines containing any of these strings will be excluded from the full log.
    | This setting is only active when the function is enabled via the user interface.
    |
    */

    'shorter_stack_trace_excludes' => [
        '/vendor/symfony/',
        '/vendor/laravel/framework/',
        '/vendor/barryvdh/laravel-debugbar/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache driver
    |--------------------------------------------------------------------------
    | Cache driver to use for storing the log indices. Indices are used to speed up
    | log navigation. Defaults to your application's default cache driver.
    |
    */

    'cache_driver' => env('LOG_VIEWER_CACHE_DRIVER', null),

    /*
    |--------------------------------------------------------------------------
    | Chunk size when scanning log files lazily
    |--------------------------------------------------------------------------
    | The size in MB of files to scan before updating the progress bar when searching across all files.
    |
    */

    'lazy_scan_chunk_size_in_mb' => 50,

    'strip_extracted_context' => true,
];
