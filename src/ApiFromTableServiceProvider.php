<?php

declare(strict_types=1);

namespace Cxuan1225\LaravelApiFromTable;

use Cxuan1225\LaravelApiFromTable\Console\ApiFromTableCommand;
use Illuminate\Support\ServiceProvider;

class ApiFromTableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/api-from-table.php',
            'api-from-table',
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ApiFromTableCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/api-from-table.php' => config_path('api-from-table.php'),
            ], 'api-from-table-config');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/vendor/api-from-table'),
            ], 'api-from-table-stubs');
        }
    }
}
