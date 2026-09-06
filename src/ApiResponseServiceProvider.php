<?php

declare(strict_types=1);

namespace Bahadovic\ApiResponse;

use Bahadovic\ApiResponse\Contracts\ApiResponseInterface;
use Illuminate\Support\ServiceProvider;

class ApiResponseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-response.php', 'api-response');

        $this->app->singleton(ApiResponseInterface::class, function () {
            return new ApiResponse;
        });

        $this->app->alias(ApiResponseInterface::class, 'api-response');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-response.php' => config_path('api-response.php'),
            ], 'api-response-config');
        }
    }
}
