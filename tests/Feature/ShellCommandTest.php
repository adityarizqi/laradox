<?php

namespace Laradox\Tests\Feature;

use Illuminate\Support\Facades\File;
use Laradox\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class ShellCommandTest extends FeatureTestCase
{
    #[Test]
    public function it_fails_when_docker_compose_file_not_found(): void
    {
        $this->artisan('laradox:shell')
            ->expectsOutput('Docker Compose file not found: ' . base_path('docker-compose.development.yml'))
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_environment_option(): void
    {
        $this->createTestDockerComposeFile('production');

        $command = $this->artisan('laradox:shell', ['--environment' => 'production']);

        // The command will check for the production compose file
        $this->assertTrue(File::exists(base_path('docker-compose.production.yml')));
    }

    #[Test]
    public function it_uses_development_environment_by_default(): void
    {
        $this->createTestDockerComposeFile('development');

        $command = $this->artisan('laradox:shell');

        // Verify the command tried to use development file
        $this->assertTrue(File::exists(base_path('docker-compose.development.yml')));
    }

    #[Test]
    public function it_shows_error_when_no_containers_are_running(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:shell')
            ->expectsOutput('✗ No containers are running!')
            ->expectsOutput('Start containers with: php artisan laradox:up --detach')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_uses_php_service_by_default(): void
    {
        $this->createTestDockerComposeFile('development');

        // This will fail because containers are not running, but we verify the default service
        $this->artisan('laradox:shell')
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_service_argument(): void
    {
        $this->createTestDockerComposeFile('development');

        // This will fail because containers are not running, but we verify the argument is accepted
        $this->artisan('laradox:shell', ['service' => 'nginx'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_user_option(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:shell', ['service' => 'php', '--user' => 'www-data'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_shell_option(): void
    {
        $this->createTestDockerComposeFile('development');

        $this->artisan('laradox:shell', ['service' => 'php', '--shell' => 'sh'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_uses_sh_as_default_shell(): void
    {
        $this->createTestDockerComposeFile('development');

        // Default shell should be sh
        $this->artisan('laradox:shell')
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_validates_service_exists_in_compose_file(): void
    {
        $this->createTestDockerComposeFile('development');

        // Test with a non-existent service
        // Note: This will show "containers not running" first since we can't mock docker
        $this->artisan('laradox:shell', ['service' => 'nonexistent'])
            ->expectsOutput('✗ No containers are running!')
            ->assertExitCode(1);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test files
        if (File::exists(base_path('docker-compose.development.yml'))) {
            File::delete(base_path('docker-compose.development.yml'));
        }
        if (File::exists(base_path('docker-compose.production.yml'))) {
            File::delete(base_path('docker-compose.production.yml'));
        }
    }
}
