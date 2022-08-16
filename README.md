![log-viewer-screenshot](https://user-images.githubusercontent.com/8697942/184591230-e6dfb1e6-215e-418b-a61e-58c9cdbb392a.png)

# Fast and easy-to-use Log Viewer for Laravel

[![Packagist](https://img.shields.io/packagist/v/opcodesio/log-viewer.svg?style=flat-square)](https://packagist.org/packages/opcodesio/log-viewer)
[![Packagist](https://img.shields.io/packagist/dm/opcodesio/log-viewer.svg?style=flat-square)](https://packagist.org/packages/opcodesio/log-viewer)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/opcodesio/log-viewer.svg?style=flat-square)](https://packagist.org/packages/opcodesio/log-viewer)
[![Laravel Version](https://img.shields.io/badge/Laravel-8.x,%209.x-brightgreen.svg?style=flat-square)](https://packagist.org/packages/opcodesio/log-viewer)

[OPcodes's](https://www.opcodes.io/) **Log Viewer** is a perfect companion for your [Laravel](https://laravel.com/) app.

You will no longer need to read the raw Laravel log files trying to find what you're looking for.

Log Viewer helps you quickly and clearly see individual log entries, to **search**, **filter**, and make sense of your Laravel logs **fast**. It is free and easy to install.

### Features
- 📂 **View all the Laravel logs** in your `storage/logs` directory,
- 🔍 **Search** the logs,
- 🎚 **Filter** by log level (error, info, debug, etc),
- 🔗 **Sharable links** to individual log entries,
- 💾 **Download & delete** log files from the UI,
- ☑️ **Horizon** log support,
- and more...

## Requirements

Log Viewer requires:
- **PHP 8.0** or higher
- **Laravel 8, 9** or higher

## Installation

You can install the package via composer:

```bash
composer require opcodesio/log-viewer
```

**That's it!**

The Log Viewer can now be accessed by visiting `{APP_URL}/log-viewer` in your browser.

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --tag="log-viewer-config"
```

This is the contents of the published config file:

```php
return [
    /**
     * Log Viewer route path.
     */
    'route_path' => 'log-viewer',

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
     * Log Viewer route middleware. The 'web' middleware is applied by default.
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
     * Shorter stack trace filters. Any lines containing any of the below strings will be excluded from the full log.
     * Only active when the setting is on, which can be toggled in the user interface.
     */
    'shorter_stack_trace_excludes' => [
        '/vendor/symfony/',
        '/vendor/laravel/framework/',
        '/vendor/barryvdh/laravel-debugbar/'
    ]
];
```

## Usage

Once installed, simply visit `{APP_URL}/log-viewer` in your browser.

You can change the route and its middleware in the `config/log-viewer.php`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/arukompas/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Arunas Skirius](https://github.com/arukompas)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
