<?php

namespace Arukompas\BetterLogViewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Arukompas\BetterLogViewer\BetterLogViewer
 */
class BetterLogViewer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'better-log-viewer';
    }
}
