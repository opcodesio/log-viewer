<?php

namespace Opcodes\LogViewer\Logs;

class LogType
{
    const DEFAULT = 'laravel';

    const LARAVEL = 'laravel';

    const HTTP_ACCESS = 'http_access';

    const HTTP_ERROR_APACHE = 'http_error_apache';

    const HTTP_ERROR_NGINX = 'http_error_nginx';

    const HORIZON_OLD = 'horizon_old';

    const HORIZON = 'horizon';

    const PHP_FPM = 'php_fpm';

    const POSTGRES = 'postgres';

    const REDIS = 'redis';

    const SUPERVISOR = 'supervisor';
}
