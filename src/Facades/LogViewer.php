<?php

namespace Opcodes\LogViewer\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Opcodes\LogViewer\LogFile;

/**
 * @see \Opcodes\LogViewer\LogViewerService
 *
 * @method static LogFile[]|Collection getFiles()
 * @method static LogFile|null getFile(string $fileName)
 * @method static void clearFileCache()
 * @method static array getRouteMiddleware()
 * @method static string getRoutePrefix()
 * @method static void auth($callback = null)
 */
class LogViewer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log-viewer';
    }
}
