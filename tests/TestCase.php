<?php

namespace Laradox\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Laradox\LaradoxServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up test artifacts
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
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
     * Clean up test files.
     */
    protected function cleanupTestFiles(): void
    {
        $filesToClean = [
            base_path('docker'),
            base_path('composer'),
            base_path('npm'),
            base_path('php'),
            base_path('docker-compose.development.yml'),
            base_path('docker-compose.production.yml'),
            config_path('laradox.php'),
        ];

        foreach ($filesToClean as $file) {
            if (File::exists($file)) {
                if (File::isDirectory($file)) {
                    File::deleteDirectory($file);
                } else {
                    File::delete($file);
                }
            }
        }
    }

    /**
     * Create a test docker compose file.
     */
    protected function createTestDockerComposeFile(string $env = 'development'): string
    {
        $path = base_path("docker-compose.{$env}.yml");
        $content = <<<YAML
version: '3.8'
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
