<?php

namespace Opcodes\LogViewer\Facades;

use Opcodes\LogViewer\LogFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Opcodes\LogViewer\LogViewer
 *
 * @method static Collection|LogFile[] getFiles()
 * @method static LogFile|null getFile(string $fileName)
 * @method static void clearFileCache()
 * @method static array getRouteMiddleware()
 * @method static string getRoutePrefix()
 */
class LogViewer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log-viewer';
    }
}
