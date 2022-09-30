<div align="center">
    <p>
        <h1>Log Viewer<br/>Easy-to-use, fast, and beautiful</h1>
    </p>
</div>

<p align="center">
    <a href="#features">Features</a> |
    <a href="#installation">Installation</a> |
    <a href="#configuration">Configuration</a> |
    <a href="#authorization">Authorization</a> |
    <a href="#troubleshooting">Troubleshooting</a> |
    <a href="#credits">Credits</a>
</p>

<p align="center">
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/v/opcodesio/log-viewer.svg?style=flat-square" alt="Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/dm/opcodesio/log-viewer.svg?style=flat-square" alt="Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/packagist/php-v/opcodesio/log-viewer.svg?style=flat-square" alt="PHP from Packagist"></a>
<a href="https://packagist.org/packages/opcodesio/log-viewer"><img src="https://img.shields.io/badge/Laravel-8.x,%209.x-brightgreen.svg?style=flat-square" alt="Laravel Version"></a>
</p>

![log-viewer-light-dark](https://user-images.githubusercontent.com/8697942/186705175-d51db6ef-1615-4f94-aa1e-3ecbcb29ea24.png)


[OPcodes's](https://www.opcodes.io/) **Log Viewer** is a perfect companion for your [Laravel](https://laravel.com/) app.

You will no longer need to read the raw Laravel log files trying to find what you're looking for.

Log Viewer helps you quickly and clearly see individual log entries, to **search**, **filter**, and make sense of your Laravel logs **fast**. It is free and easy to install.

> 📺 **[Watch a quick 4-minute video](https://www.youtube.com/watch?v=q7SnF2vubRE)** showcasing some Log Viewer features.

### Features

- 📂 **View all the Laravel logs** in your `storage/logs` directory,
- 🔍 **Search** the logs,
- 🎚 **Filter** by log level (error, info, debug, etc.),
- 🔗 **Sharable links** to individual log entries,
- 🌑 **Dark mode**
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

## Configuration

### Config file

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
    | Log Viewer route middleware.
    |--------------------------------------------------------------------------
    | The middleware should enable session and cookies support in order for the Log Viewer to work.
    | The 'web' middleware will be applied automatically if empty.
    |
    */

    'middleware' => ['web'],
```

## Authorization

Several things can be configured to have different access based on the user logged in, or the log file in action.

Here are the permissions and how to set them up.

### Authorizing Log Viewer access

You can limit who has access to the Log Viewer in several ways.

#### Via "auth" callback
You can limit access to the Log Viewer by providing a custom authorization callback to the `LogViewer::auth()` method within your `AppServiceProvider`, like so:

```php
use Opcodes\LogViewer\Facades\LogViewer;

/**
 * Bootstrap any application services.
 *
 * @return void
 */
public function boot()
{
    LogViewer::auth(function ($request) {
        // return true to allow viewing the Log Viewer.
    });

    // Here's an example:
    LogViewer::auth(function ($request) {
        return $request->user()
            && in_array($request->user()->email, [
                // 'john@example.com',
            ]);
    });
}
```

#### Via "viewLogViewer" gate

Another easy way to limit access to the Log Viewer is via [Laravel Gates](https://laravel.com/docs/9.x/authorization#gates). Just define a `viewLogViewer` authorization gate in your `App\Providers\AuthServiceProvider` class:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;
 
/**
 * Register any authentication / authorization services.
 *
 * @return void
 */
public function boot()
{
    $this->registerPolicies();
 
    Gate::define('viewLogViewer', function (?User $user) {
        // return true if the user is allowed access to the Log Viewer
    });
}
```

#### Via middleware

You can easily add [authentication](https://laravel.com/docs/9.x/authentication#protecting-routes) to log viewing routes using popular `auth` middleware in the `config/log-viewer.php`.

If your application doesn't use the default authentication solutions, you can use the `auth.basic` [HTTP Basic Authentication](https://laravel.com/docs/9.x/authentication#http-basic-authentication) middleware.

_**Note:** By default, the `auth.basic` middleware will assume the email column on your users database table is the user's "username"._

See the `auth` middleware configuration below:
```php
    /*
    |--------------------------------------------------------------------------
    | Log Viewer route middleware.
    |--------------------------------------------------------------------------
    | The middleware should enable session and cookies support in order for the Log Viewer to work.
    | The 'web' middleware will be applied automatically if empty.
    |
    */

    'middleware' => ['web', 'auth'],
```

For authorization using Spatie permissions [see this discussion](https://github.com/opcodesio/log-viewer/discussions/16)

### Authorizing log file download

You can limit the ability to download log files via [Laravel Gates](https://laravel.com/docs/9.x/authorization#gates). Just define a `downloadLogFile` authorization gate in your `App\Providers\AuthServiceProvider` class:

```php
use App\Models\User;
use Opcodes\LogViewer\LogFile;
use Illuminate\Support\Facades\Gate;

/**
 * Register any authentication / authorization services.
 *
 * @return void
 */
public function boot()
{
    $this->registerPolicies();
 
    Gate::define('downloadLogFile', function (?User $user, LogFile $file) {
        // return true if the user is allowed to download the specific log file.
    });
}
```

#### Authorizing folder downloads

You can also limit whether whole folders can be downloaded by defining a `downloadLogFolder` authorization gate:

```php
use Opcodes\LogViewer\LogFolder;

//...

Gate::define('downloadLogFolder', function (?User $user, LogFolder $folder) {
    // return true if the user is allowed to download the whole folder.
});
```

**NOTE:** Individual file permissions are also checked before downloading them, to avoid accidental downloads of protected log files.

### Authorizing log file deletion

You can limit the ability to delete log files via [Laravel Gates](https://laravel.com/docs/9.x/authorization#gates). Just define a `deleteLogFile` authorization gate in your `App\Providers\AuthServiceProvider` class:

```php
use App\Models\User;
use Opcodes\LogViewer\LogFile;
use Illuminate\Support\Facades\Gate;

/**
 * Register any authentication / authorization services.
 *
 * @return void
 */
public function boot()
{
    $this->registerPolicies();
 
    Gate::define('deleteLogFile', function (?User $user, LogFile $file) {
        // return true if the user is allowed to delete the specific log file.
    });
}
```

#### Authorizing folder deletion

You can also limit whether whole folders can be deleted by defining a `deleteLogFolder` authorization gate:

```php
use Opcodes\LogViewer\LogFolder;

//...

Gate::define('deleteLogFolder', function (?User $user, LogFolder $folder) {
    // return true if the user is allowed to delete the whole folder.
});
```

**NOTE:** Individual file permissions are also checked before deleting them, to avoid accidental deletion of protected log files.

## Troubleshooting

Here are some common problems and solutions.

### Problem: "Livewire not defined" or other errors in the browser's console

This is most often caused by your project being served from a sub-folder, like `example.com/your-laravel-project/log-viewer`.

Livewire by default tries to load its resources from the root of the domain, like `example.com/livewire/livewire.js`, but if that's outside your project's sub-folder, then you need to set a different asset_url. You can [read more about it here](https://laravel-livewire.com/docs/2.x/installation#configuring-the-asset-base-url).

Fortunately, the fix is easy:

1. Publish the Livewire config:
```shell
php artisan livewire:publish --config
```
2. Set the `asset_url` option in the `config/livewire.php` file to your app's subdomain:
```php
    'asset_url' => '/your-laravel-project',
```

### Problem: Logs not loading

At the moment, Log Viewer is only able to process [Laravel logs](https://laravel.com/docs/9.x/logging) that look something like this:

```
[2022-08-25 11:16:17] local.DEBUG: Example log entry for the level debug {"one":1,"two":"two","three":[1,2,3]}
Multiple lines are allowed
and will be picked up as contents
of the same log entry.
```

If your logs are structured differently, then you'll have to wait until we ship support for custom log formats. Otherwise, please adjust your log format to Laravel's default.

## Screenshots

Read the **[release blog post](https://arunas.dev/log-viewer-for-laravel/)**  for screenshots and more information about Log Viewer's features.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Arunas Skirius](https://github.com/arukompas)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
