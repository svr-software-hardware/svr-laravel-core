<?php

namespace SVR\LaravelCore\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SVR\LaravelCore\SvrLaravelCoreServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SvrLaravelCoreServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('svr-core.public_id.collation', null);
    }
}
