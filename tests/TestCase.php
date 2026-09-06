<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse\Tests;

use Bahadovic\ApiResponse\ApiResponseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ApiResponseServiceProvider::class,
        ];
    }
}
