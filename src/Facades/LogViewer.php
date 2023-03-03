<?php

namespace Opcodes\LogViewer\Facades;

use Illuminate\Support\Facades\Facade;
use Opcodes\LogViewer\Host;
use Opcodes\LogViewer\HostCollection;
use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogFileCollection;
use Opcodes\LogViewer\LogFolder;
use Opcodes\LogViewer\LogFolderCollection;

/**
 * @see \Opcodes\LogViewer\LogViewerService
 *
 * @method static string version()
 * @method static bool assetsAreCurrent()
 * @method static bool supportsHostsFeature()
 * @method static void resolveHostsUsing(callable $callback)
 * @method static Host[]|HostCollection getHosts()
 * @method static Host|null getHost(?string $hostIdentifier)
 * @method static LogFolder[]|LogFolderCollection getFilesGroupedByFolder()
 * @method static LogFolder|null getFolder(?string $folderIdentifier)
 * @method static LogFile[]|LogFileCollection getFiles()
 * @method static LogFile|null getFile(string $fileIdentifier)
 * @method static void clearFileCache()
 * @method static string|null getRouteDomain()
 * @method static array getRouteMiddleware()
 * @method static string getRoutePrefix()
 * @method static void auth($callback = null)
 * @method static void setMaxLogSize(int $bytes)
 * @method static int maxLogSize()
 * @method static int lazyScanChunkSize()
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
