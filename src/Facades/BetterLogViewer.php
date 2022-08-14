<?php

namespace Arukompas\BetterLogViewer\Facades;

use Arukompas\BetterLogViewer\LogFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Arukompas\BetterLogViewer\BetterLogViewer
 *
 * @method static Collection|LogFile[] getFiles()
 * @method static LogFile|null getFile(string $fileName)
 * @method static void clearFileCache()
 * @method static array getRouteMiddleware()
 * @method static string getRoutePrefix()
 */
class BetterLogViewer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'better-log-viewer';
    }
}
