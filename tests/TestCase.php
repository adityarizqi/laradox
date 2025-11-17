<?php

namespace Laradox\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Laradox\LaradoxServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaradoxServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('laradox.domain', 'test.docker.localhost');
        $app['config']->set('laradox.environment', 'development');
        $app['config']->set('laradox.additional_domains', [
            '*.docker.localhost',
            'docker.localhost',
        ]);
        $app['config']->set('laradox.ssl.cert_path', base_path('docker/nginx/ssl/cert.pem'));
        $app['config']->set('laradox.ssl.key_path', base_path('docker/nginx/ssl/key.pem'));
    }

    /**
     * Create a test docker compose file.
     */
    protected function createTestDockerComposeFile(string $env = 'development'): string
    {
        $path = base_path("docker-compose.{$env}.yml");
        $content = <<<YAML
services:
  app:
    image: php:8.4
    ports:
      - "8080:8080"
YAML;

        File::put($path, $content);
        return $path;
    }

    /**
     * Create a test SSL directory.
     */
    protected function createTestSslDirectory(): string
    {
        $path = base_path('docker/nginx/ssl');
        File::makeDirectory($path, 0755, true);
        return $path;
    }
}
