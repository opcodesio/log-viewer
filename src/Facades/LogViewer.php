<?php

namespace Opcodes\LogViewer\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Opcodes\LogViewer\LogFile;

/**
 * @see \Opcodes\LogViewer\LogViewerService
 *
 * @method static string version()
 * @method static LogFile[]|Collection getFiles()
 * @method static LogFile|null getFile(string $fileIdentifier)
 * @method static void clearFileCache()
 * @method static array getRouteMiddleware()
 * @method static string getRoutePrefix()
 * @method static void auth($callback = null)
 * @method static void setMaxLogSize(int $bytes)
 * @method static int maxLogSize()
 * @method static string laravelRegexPattern()
 * @method static string logMatchPattern()
 * @method static string basePathForLogs()
 */
class LogViewer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log-viewer';
    }
}
