<?php

namespace Opcodes\LogViewer\Http\Middleware;

use Illuminate\Routing\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Credits to Laravel Sanctum.
 *
 * @link https://github.com/laravel/sanctum/blob/3.x/src/Http/Middleware/EnsureFrontendRequestsAreStateful.php
 */
class EnsureFrontendRequestsAreStateful
{
    /**
     * Handle the incoming requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        $this->configureSecureCookieSessions();

        return (new Pipeline(app()))->send($request)->through(static::fromFrontend($request) ? [
            function ($request, $next) {
                $request->attributes->set('sanctum', true);

                return $next($request);
            },
            static::resolveMiddleware('sanctum.middleware.encrypt_cookies', \Illuminate\Cookie\Middleware\EncryptCookies::class),
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            static::resolveMiddleware('sanctum.middleware.verify_csrf_token', \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class),
        ] : [])->then(function ($request) use ($next) {
            return $next($request);
        });
    }

    /**
     * Configure secure cookie sessions.
     *
     * @return void
     */
    protected function configureSecureCookieSessions()
    {
        config([
            'session.http_only' => true,
            'session.same_site' => 'lax',
        ]);
    }

    /**
     * Determine if the given request is from the first-party application frontend.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function fromFrontend($request)
    {
        $domain = $request->headers->get('referer') ?: $request->headers->get('origin');

        if (is_null($domain)) {
            return false;
        }

        $domain = Str::replaceFirst('https://', '', $domain);
        $domain = Str::replaceFirst('http://', '', $domain);
        $domain = Str::endsWith($domain, '/') ? $domain : "{$domain}/";

        $stateful = array_filter(config('log-viewer.api_stateful_domains') ?? config('sanctum.stateful') ?? self::defaultStatefulDomains());

        $matchesStatefulDomains = Str::is(Collection::make($stateful)->map(function ($uri) {
            return trim($uri).'/*';
        })->all(), $domain);

        if ($matchesStatefulDomains) {
            return true;
        }

        // If APP_URL is not configured, allow same-domain requests as a fallback
        if (empty(config('app.url'))) {
            return self::isSameDomainRequest($request, $domain);
        }

        return false;
    }

    /**
     * Check if the referer/origin domain matches the current request's domain.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $refererDomain
     * @return bool
     */
    protected static function isSameDomainRequest($request, $refererDomain)
    {
        $currentHost = $request->getHost();
        $currentPort = $request->getPort();

        // Build current domain with port if not default
        $currentDomain = $currentHost;
        if (! in_array($currentPort, [80, 443])) {
            $currentDomain .= ':'.$currentPort;
        }

        // Extract host:port from referer domain (strip path)
        $refererHostPort = explode('/', $refererDomain)[0];

        return $refererHostPort === $currentDomain;
    }

    /**
     * Resolve middleware class from config with fallback.
     */
    protected static function resolveMiddleware(string $configKey, string $default): string
    {
        $middleware = config($configKey, $default);

        return class_exists($middleware) ? $middleware : $default;
    }

    protected static function defaultStatefulDomains(): array
    {
        return explode(',', sprintf(
            '%s%s',
            'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
            self::currentApplicationUrlWithPort()
        ));
    }

    /**
     * Get the current application URL from the "APP_URL" environment variable - with port.
     *
     * @return string
     */
    protected static function currentApplicationUrlWithPort()
    {
        $appUrl = config('app.url');

        return $appUrl ? ','.parse_url($appUrl, PHP_URL_HOST).(parse_url($appUrl, PHP_URL_PORT) ? ':'.parse_url($appUrl, PHP_URL_PORT) : '') : '';
    }
}
