<?php

namespace TeknikHub\FileManager\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TeknikHub\FileManager\FileManagerServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            FileManagerServiceProvider::class,
        ];
    }
}
