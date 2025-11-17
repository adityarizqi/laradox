<?php

namespace Laradox;

use Illuminate\Support\ServiceProvider;
use Laradox\Console\InstallCommand;
use Laradox\Console\SetupSSLCommand;
use Laradox\Console\UpCommand;
use Laradox\Console\DownCommand;

class LaradoxServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SetupSSLCommand::class,
                UpCommand::class,
                DownCommand::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/laradox.php' => config_path('laradox.php'),
            ], 'laradox-config');

            // Publish Docker files
            $this->publishes([
                __DIR__.'/../stubs/docker' => base_path('docker'),
            ], 'laradox-docker');

            // Publish Docker Compose files
            $this->publishes([
                __DIR__.'/../stubs/docker-compose.development.yml' => base_path('docker-compose.development.yml'),
                __DIR__.'/../stubs/docker-compose.production.yml' => base_path('docker-compose.production.yml'),
            ], 'laradox-compose');

            // Publish helper scripts
            $this->publishes([
                __DIR__.'/../stubs/composer' => base_path('composer'),
                __DIR__.'/../stubs/npm' => base_path('npm'),
                __DIR__.'/../stubs/php' => base_path('php'),
            ], 'laradox-scripts');

            // Publish all at once
            $this->publishes([
                __DIR__.'/../config/laradox.php' => config_path('laradox.php'),
                __DIR__.'/../stubs/docker' => base_path('docker'),
                __DIR__.'/../stubs/docker-compose.development.yml' => base_path('docker-compose.development.yml'),
                __DIR__.'/../stubs/docker-compose.production.yml' => base_path('docker-compose.production.yml'),
                __DIR__.'/../stubs/composer' => base_path('composer'),
                __DIR__.'/../stubs/npm' => base_path('npm'),
                __DIR__.'/../stubs/php' => base_path('php'),
            ], 'laradox');
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laradox.php',
            'laradox'
        );
    }
}
