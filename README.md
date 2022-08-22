<div align="center">
    <p>
        <h1>Log Viewer<br/>Fast and easy-to-use</h1>
    </p>
</div>

<p align="center">
    <a href="#features">Features</a> |
    <a href="#installation">Installation</a> |
    <a href="#configuration">Configuration</a> |
    <a href="#credits">Credits</a>
</p>

<p align="center">
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/v/opcodesio/log-viewer.svg?style=flat-square" alt="Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/dm/opcodesio/log-viewer.svg?style=flat-square" alt="Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/php-v/opcodesio/log-viewer.svg?style=flat-square" alt="PHP from Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/badge/Laravel-8.x,%209.x-brightgreen.svg?style=flat-square" alt="Laravel Version"></a>
</p>

![log-viewer-screenshot](https://user-images.githubusercontent.com/8697942/184591230-e6dfb1e6-215e-418b-a61e-58c9cdbb392a.png)


[OPcodes's](https://www.opcodes.io/) **Log Viewer** is a perfect companion for your [Laravel](https://laravel.com/) app.

You will no longer need to read the raw Laravel log files trying to find what you're looking for.

Log Viewer helps you quickly and clearly see individual log entries, to **search**, **filter**, and make sense of your Laravel logs **fast**. It is free and easy to install.

### Features

- 📂 **View all the Laravel logs** in your `storage/logs` directory,
- 🔍 **Search** the logs,
- 🎚 **Filter** by log level (error, info, debug, etc.),
- 🔗 **Sharable links** to individual log entries,
- 💾 **Download & delete** log files from the UI,
- ☑️ **Horizon** log support,
- and more...

## Get Started

### Requirements

- **PHP 8.0+**
- **Laravel 8+**

### Installation

To install the package via composer, Run:

```bash
composer require opcodesio/log-viewer
```

### Usage

Once the installation is complete, you will be able to access **Log Viewer** directly in your browser.

By default, the application is available at: `{APP_URL}/log-viewer`.

(for example: `https://my-app.test/log-viewer`)

### Configuration

#### Config file

To publish the [config file](https://github.com/opcodesio/log-viewer/blob/main/config/log-viewer.php), run:

```bash
php artisan vendor:publish --tag="log-viewer-config"
```

### Route & Middleware

You can easily change the default route and its middleware in the config/log-viewer.php.

See the configuration below:

```php
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
    | Log Viewer route middleware.
    |--------------------------------------------------------------------------
    | The middleware should enable session and cookies support in order for the Log Viewer to work.
    | The 'web' middleware will be applied automatically if empty.
    |
    */

    'middleware' => ['web'],
```

## Screenshots

Read the **[release blog post](https://arunas.dev/log-viewer-for-laravel/)**  for screenshots and more information about Log Viewer's features.

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
