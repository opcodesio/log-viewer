<?php

namespace Opcodes\LogViewer\Tests;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

class DeferPackageProviderTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            RouteServiceProvider::class,
        ];
    }
}
